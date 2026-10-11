<?php
// =============================================================================
// ENDPOINT : POST /api/panier/ajouter.php
// Corps JSON : {"offre_id": 1, "quantite": 1}
// =============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

activerCors();

// Seule la méthode POST est autorisée.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST, OPTIONS');
    repondreJson(['erreur' => 'Méthode non autorisée.'], 405);
}

$utilisateur = exigerUtilisateurConnecte($pdo);
$donnees = lireCorpsJson();

// Valide les identifiants et les quantités sans conversion silencieuse.
$offreId = $donnees['offre_id'] ?? null;
$quantite = $donnees['quantite'] ?? 1;

if (
    !(is_int($offreId) || (is_string($offreId) && ctype_digit($offreId)))
    || (int) $offreId < 1
    || !(is_int($quantite) || (is_string($quantite) && ctype_digit($quantite)))
    || (int) $quantite < 1
    || (int) $quantite > 100
) {
    repondreJson([
        'erreur' => 'Identifiant d’offre ou quantité invalide (1 à 100 unités).'
    ], 400);
}

$offreId = (int) $offreId;
$quantite = (int) $quantite;

try {
    $pdo->beginTransaction();

    // Verrouille le panier existant pour sérialiser ses modifications.
    $requete = $pdo->prepare(
        'SELECT id FROM paniers WHERE utilisateur_id = ? FOR UPDATE'
    );
    $requete->execute([$utilisateur['id']]);
    $panier = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$panier) {
        $requete = $pdo->prepare(
            'INSERT INTO paniers (utilisateur_id) VALUES (?)'
        );
        $requete->execute([$utilisateur['id']]);
        $panierId = (int) $pdo->lastInsertId();
    } else {
        $panierId = (int) $panier['id'];
    }

    // Verrouille l'offre pour contrôler son état et son stock.
    $requete = $pdo->prepare(
        "SELECT id, type, quantite_stock
         FROM offres
         WHERE id = ? AND statut_moderation = 'publiee'
         FOR UPDATE"
    );
    $requete->execute([$offreId]);
    $offre = $requete->fetch(PDO::FETCH_ASSOC);

    if (!$offre) {
        $pdo->rollBack();
        repondreJson(['erreur' => 'Cette offre n’est plus disponible.'], 404);
    }

    // Verrouille la ligne existante, s'il y en a une.
    $requete = $pdo->prepare(
        'SELECT id, quantite
         FROM lignes_panier
         WHERE panier_id = ? AND offre_id = ?
         FOR UPDATE'
    );
    $requete->execute([$panierId, $offreId]);
    $ligne = $requete->fetch(PDO::FETCH_ASSOC);

    $quantiteExistante = $ligne ? (int) $ligne['quantite'] : 0;

    if (
        $quantiteExistante < 0
        || $quantiteExistante > PHP_INT_MAX - $quantite
    ) {
        $pdo->rollBack();
        repondreJson(['erreur' => 'Quantité invalide.'], 400);
    }

    $nouvelleQuantite = $quantiteExistante + $quantite;

    if ($nouvelleQuantite > 100) {
        $pdo->rollBack();
        repondreJson([
            'erreur' => 'La quantité totale par offre ne peut pas dépasser 100.'
        ], 400);
    }

    if ($offre['type'] === 'physique') {
        $stock = $offre['quantite_stock'];

        if ($stock === null || !is_numeric($stock) || (int) $stock < $nouvelleQuantite) {
            $pdo->rollBack();
            repondreJson(['erreur' => 'Stock insuffisant.'], 400);
        }
    }

    if ($ligne) {
        $requete = $pdo->prepare(
            'UPDATE lignes_panier SET quantite = ? WHERE id = ? AND panier_id = ?'
        );
        $requete->execute([$nouvelleQuantite, $ligne['id'], $panierId]);
    } else {
        $requete = $pdo->prepare(
            'INSERT INTO lignes_panier (panier_id, offre_id, quantite)
             VALUES (?, ?, ?)'
        );
        $requete->execute([$panierId, $offreId, $nouvelleQuantite]);
    }

    $pdo->commit();

    repondreJson(['message' => 'Ajouté au panier.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Les détails techniques restent dans les journaux du serveur.
    error_log('Erreur ajout panier : ' . $e->getMessage());

    repondreJson([
        'erreur' => 'Impossible d’ajouter cette offre au panier pour le moment.'
    ], 500);
}