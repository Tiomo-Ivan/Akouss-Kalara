<?php
// =============================================================================
// ENDPOINT : POST /api/commandes/creer.php
// RÔLE : transforme le panier en commande et fige les prix.
// Aucun paiement n'est déclenché par cet endpoint.
// =============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

activerCors();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST, OPTIONS');
    repondreJson(['erreur' => 'Méthode non autorisée.'], 405);
}

$utilisateur = exigerUtilisateurConnecte($pdo);
$donnees = lireCorpsJson();

$modeLivraison = $donnees['mode_livraison'] ?? 'domicile';
if (!is_string($modeLivraison) ||
    !in_array($modeLivraison, ['domicile', 'retrait', 'telechargement'], true)) {
    repondreJson(['erreur' => 'Mode de livraison invalide.'], 400);
}

$transactionActive = false;

try {
    $pdo->beginTransaction();
    $transactionActive = true;

    // Verrouille le panier afin d'éviter deux créations simultanées
    // à partir du même panier.
    $requete = $pdo->prepare(
        'SELECT id FROM paniers WHERE utilisateur_id = ? FOR UPDATE'
    );
    $requete->execute([$utilisateur['id']]);
    $panier = $requete->fetch();

    if (!$panier) {
        $pdo->rollBack();
        $transactionActive = false;
        repondreJson(['erreur' => 'Panier introuvable.'], 400);
    }

    // Verrouille les lignes du panier et relit les informations des offres.
    $requeteLignes = $pdo->prepare(
        "SELECT lp.id AS ligne_panier_id,
                lp.offre_id,
                lp.quantite,
                o.prix,
                o.quantite_stock,
                o.type,
                o.statut_moderation
         FROM lignes_panier lp
         JOIN offres o ON o.id = lp.offre_id
         WHERE lp.panier_id = ?
         ORDER BY lp.offre_id
         FOR UPDATE"
    );
    $requeteLignes->execute([$panier['id']]);
    $lignesPanier = $requeteLignes->fetchAll();

    if (empty($lignesPanier)) {
        $pdo->rollBack();
        $transactionActive = false;
        repondreJson(['erreur' => 'Le panier est vide.'], 400);
    }

    // Valide toutes les lignes avant de créer la commande.
    foreach ($lignesPanier as $ligne) {
        $quantite = filter_var(
            $ligne['quantite'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($quantite === false) {
            throw new DomainException('Quantité invalide dans le panier.');
        }

        if ($ligne['statut_moderation'] !== 'publiee') {
            throw new DomainException('Une offre du panier n’est plus disponible.');
        }

        if (!is_numeric($ligne['prix']) ||
            !is_finite((float) $ligne['prix']) ||
            (float) $ligne['prix'] < 0) {
            throw new DomainException('Prix invalide pour une offre.');
        }

        if (!in_array($ligne['type'], ['physique', 'numerique'], true)) {
            throw new DomainException('Type d’offre invalide.');
        }

        if ($ligne['type'] === 'physique' && $modeLivraison === 'telechargement') {
            throw new DomainException(
                'Les articles physiques nécessitent une livraison à domicile ou un retrait.'
            );
        }

        if ($ligne['type'] === 'physique' &&
            ($ligne['quantite_stock'] === null ||
             !is_numeric($ligne['quantite_stock']) ||
             (int) $ligne['quantite_stock'] < $quantite)) {
            throw new DomainException('Stock insuffisant pour un article du panier.');
        }
    }

    // Convertit les prix DECIMAL en centimes sans passer par un float.
    // DECIMAL(10,2) autorise au maximum 99 999 999,99 par montant.
    $montantMaximumCentimes = 9999999999;
    $montantTotalCentimes = 0;
    $prixCentimesParOffre = [];

    foreach ($lignesPanier as $ligne) {
        $prixTexte = (string) $ligne['prix'];

        if (!preg_match('/\\A([0-9]{1,8})(?:\\.([0-9]{1,2}))?\\z/', $prixTexte, $correspondance)) {
            throw new DomainException('Format de prix invalide.');
        }

        $partieEntiere = (int) $correspondance[1];
        $partieDecimale = str_pad($correspondance[2] ?? '', 2, '0');
        $prixCentimes = $partieEntiere * 100 + (int) $partieDecimale;
        $quantite = (int) $ligne['quantite'];

        if ($prixCentimes > 0 &&
            $quantite > intdiv($montantMaximumCentimes - $montantTotalCentimes, $prixCentimes)) {
            throw new DomainException('Montant total invalide ou trop élevé.');
        }

        $prixCentimesParOffre[(int) $ligne['offre_id']] = $prixCentimes;
        $montantTotalCentimes += $prixCentimes * $quantite;
    }

    $montantTotal = intdiv($montantTotalCentimes, 100) . '.' .
        str_pad((string) ($montantTotalCentimes % 100), 2, '0', STR_PAD_LEFT);

    $insererCommande = $pdo->prepare(
        "INSERT INTO commandes (utilisateur_id, statut, montant_total)
         VALUES (?, 'en_attente', ?)"
    );
    $insererCommande->execute([$utilisateur['id'], $montantTotal]);
    $commandeId = $pdo->lastInsertId();

    $insererLigne = $pdo->prepare(
        "INSERT INTO lignes_commande
            (commande_id, offre_id, quantite, prix_unitaire_fige, mode_livraison)
         VALUES (?, ?, ?, ?, ?)"
    );

    // La condition sur le stock est atomique : deux commandes concurrentes
    // ne peuvent pas décrémenter un stock en dessous de zéro.
    $decrementerStock = $pdo->prepare(
        'UPDATE offres
         SET quantite_stock = quantite_stock - ?
         WHERE id = ?
           AND type = \'physique\'
           AND statut_moderation = \'publiee\'
           AND quantite_stock IS NOT NULL
           AND quantite_stock >= ?'
    );

    foreach ($lignesPanier as $ligne) {
        $quantite = (int) $ligne['quantite'];
        $mode = $ligne['type'] === 'numerique'
            ? 'telechargement'
            : $modeLivraison;

        if ($ligne['type'] === 'physique') {
            $decrementerStock->execute([
                $quantite,
                $ligne['offre_id'],
                $quantite
            ]);

            if ($decrementerStock->rowCount() !== 1) {
                throw new DomainException(
                    'Le stock a changé. Actualisez votre panier et réessayez.'
                );
            }
        }

        $insererLigne->execute([
            $commandeId,
            $ligne['offre_id'],
            $quantite,
            intdiv($prixCentimesParOffre[(int) $ligne['offre_id']], 100) . '.' .
                str_pad((string) ($prixCentimesParOffre[(int) $ligne['offre_id']] % 100), 2, '0', STR_PAD_LEFT),
            $mode
        ]);
    }

    // Le panier est vidé uniquement après la création de toutes les lignes.
    $viderPanier = $pdo->prepare(
        'DELETE FROM lignes_panier WHERE panier_id = ?'
    );
    $viderPanier->execute([$panier['id']]);

    $pdo->commit();
    $transactionActive = false;

    repondreJson([
        'message' => 'Commande créée.',
        'commande_id' => (int) $commandeId,
        'montant_total' => $montantTotal
    ], 201);

} catch (DomainException $e) {
    if ($transactionActive && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    repondreJson(['erreur' => $e->getMessage()], 400);

} catch (Throwable $e) {
    if ($transactionActive && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erreur interne lors de la création de commande.');
    repondreJson([
        'erreur' => 'Erreur lors de la création de la commande.'
    ], 500);
}
