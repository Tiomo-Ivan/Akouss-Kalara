<?php
// =============================================================================
// ENDPOINT : POST /api/commandes/creer.php
// RÔLE : transforme le panier en commande (fige les prix), vide le panier.
// Ne déclenche PAS encore le paiement — ça se fait ensuite via
// /api/paiement/initier.php, séparément (étape suivante du parcours).
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$utilisateur = exigerUtilisateurConnecte($pdo);
$donnees = lireCorpsJson();
$modeLivraison = $donnees['mode_livraison'] ?? 'domicile'; // domicile | retrait | telechargement

$requete = $pdo->prepare("SELECT id FROM paniers WHERE utilisateur_id = ?");
$requete->execute([$utilisateur['id']]);
$panier = $requete->fetch();
if (!$panier) repondreJson(['erreur' => 'Panier introuvable.'], 400);

$requeteLignes = $pdo->prepare(
    "SELECT lp.*, o.prix, o.quantite_stock, o.type FROM lignes_panier lp
     JOIN offres o ON o.id = lp.offre_id WHERE lp.panier_id = ?"
);
$requeteLignes->execute([$panier['id']]);
$lignesPanier = $requeteLignes->fetchAll();

if (empty($lignesPanier)) repondreJson(['erreur' => 'Le panier est vide.'], 400);

// Vérifie les stocks une dernière fois avant de valider (évite la survente)
foreach ($lignesPanier as $ligne) {
    if ($ligne['type'] === 'physique' && $ligne['quantite_stock'] < $ligne['quantite']) {
        repondreJson(['erreur' => 'Stock insuffisant pour un des articles de votre panier.'], 400);
    }
}

$pdo->beginTransaction();
try {
    $montantTotal = 0;
    foreach ($lignesPanier as $ligne) {
        $montantTotal += $ligne['prix'] * $ligne['quantite'];
    }

    $pdo->prepare(
        "INSERT INTO commandes (utilisateur_id, statut, montant_total) VALUES (?, 'en_attente', ?)"
    )->execute([$utilisateur['id'], $montantTotal]);
    $commandeId = $pdo->lastInsertId();

    foreach ($lignesPanier as $ligne) {
        $mode = $ligne['type'] === 'numerique' ? 'telechargement' : $modeLivraison;

        $pdo->prepare(
            "INSERT INTO lignes_commande (commande_id, offre_id, quantite, prix_unitaire_fige, mode_livraison)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$commandeId, $ligne['offre_id'], $ligne['quantite'], $ligne['prix'], $mode]);

        // Décrémente le stock pour les offres physiques
        if ($ligne['type'] === 'physique') {
            $pdo->prepare("UPDATE offres SET quantite_stock = quantite_stock - ? WHERE id = ?")
                ->execute([$ligne['quantite'], $ligne['offre_id']]);
        }
    }

    // Vide le panier maintenant que la commande est créée
    $pdo->prepare("DELETE FROM lignes_panier WHERE panier_id = ?")->execute([$panier['id']]);

    $pdo->commit();
    repondreJson(['message' => 'Commande créée.', 'commande_id' => $commandeId, 'montant_total' => $montantTotal], 201);

} catch (Exception $e) {
    $pdo->rollBack();
    repondreJson(['erreur' => 'Erreur lors de la création de la commande.'], 500);
}
