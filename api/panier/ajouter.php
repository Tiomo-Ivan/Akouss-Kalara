<?php
// =============================================================================
// ENDPOINT : POST /api/panier/ajouter.php
// Body : {"offre_id": 1, "quantite": 1}
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$utilisateur = exigerUtilisateurConnecte($pdo);
$donnees = lireCorpsJson();
$offreId = $donnees['offre_id'] ?? null;
$quantite = max(1, (int)($donnees['quantite'] ?? 1));

if (!$offreId) repondreJson(['erreur' => 'offre_id manquant.'], 400);

// Vérifie que l'offre existe et est publiée
$requete = $pdo->prepare("SELECT * FROM offres WHERE id = ? AND statut_moderation = 'publiee'");
$requete->execute([$offreId]);
$offre = $requete->fetch();
if (!$offre) repondreJson(['erreur' => 'Cette offre n\'est plus disponible.'], 404);

if ($offre['type'] === 'physique' && $offre['quantite_stock'] < $quantite) {
    repondreJson(['erreur' => 'Stock insuffisant.'], 400);
}

// Récupère ou crée le panier
$requete = $pdo->prepare("SELECT id FROM paniers WHERE utilisateur_id = ?");
$requete->execute([$utilisateur['id']]);
$panier = $requete->fetch();
if (!$panier) {
    $pdo->prepare("INSERT INTO paniers (utilisateur_id) VALUES (?)")->execute([$utilisateur['id']]);
    $panierId = $pdo->lastInsertId();
} else {
    $panierId = $panier['id'];
}

// Ajoute la ligne, ou incrémente la quantité si l'offre est déjà dans le panier
$requete = $pdo->prepare("SELECT id, quantite FROM lignes_panier WHERE panier_id = ? AND offre_id = ?");
$requete->execute([$panierId, $offreId]);
$ligneExistante = $requete->fetch();

if ($ligneExistante) {
    $nouvelleQuantite = $ligneExistante['quantite'] + $quantite;
    $pdo->prepare("UPDATE lignes_panier SET quantite = ? WHERE id = ?")
        ->execute([$nouvelleQuantite, $ligneExistante['id']]);
} else {
    $pdo->prepare("INSERT INTO lignes_panier (panier_id, offre_id, quantite) VALUES (?, ?, ?)")
        ->execute([$panierId, $offreId, $quantite]);
}

repondreJson(['message' => 'Ajouté au panier.']);
