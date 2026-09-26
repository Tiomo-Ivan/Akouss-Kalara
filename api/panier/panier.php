<?php
// =============================================================================
// ENDPOINT : GET /api/panier/panier.php
// RÔLE : affiche le contenu du panier de l'utilisateur connecté.
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$utilisateur = exigerUtilisateurConnecte($pdo);

// Crée le panier s'il n'existe pas encore (premier accès)
$requete = $pdo->prepare("SELECT id FROM paniers WHERE utilisateur_id = ?");
$requete->execute([$utilisateur['id']]);
$panier = $requete->fetch();
if (!$panier) {
    $pdo->prepare("INSERT INTO paniers (utilisateur_id) VALUES (?)")->execute([$utilisateur['id']]);
    $panierId = $pdo->lastInsertId();
} else {
    $panierId = $panier['id'];
}

$requeteLignes = $pdo->prepare(
    "SELECT lp.id, lp.quantite, o.id AS offre_id, o.prix, o.type, o.quantite_stock,
            l.titre, l.auteur, u.nom_complet AS vendeur_nom
     FROM lignes_panier lp
     JOIN offres o ON o.id = lp.offre_id
     JOIN livres l ON l.id = o.livre_id
     JOIN utilisateurs u ON u.id = o.vendeur_id
     WHERE lp.panier_id = ?"
);
$requeteLignes->execute([$panierId]);
$lignes = $requeteLignes->fetchAll();

$total = 0;
foreach ($lignes as $ligne) {
    $total += $ligne['prix'] * $ligne['quantite'];
}

repondreJson(['lignes' => $lignes, 'total' => $total]);
