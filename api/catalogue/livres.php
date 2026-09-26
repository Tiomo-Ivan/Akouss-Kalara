<?php
// =============================================================================
// ENDPOINT : GET /api/catalogue/livres.php?q=recherche&categorie=slug
// RÔLE : liste les livres publiés, avec recherche et filtre optionnels.
// Public — aucune authentification requise.
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

activerCors();

$recherche = $_GET['q'] ?? '';
$categorieSlug = $_GET['categorie'] ?? '';

$sql = "SELECT l.id, l.titre, l.auteur, l.image_couverture, c.nom AS categorie_nom,
               MIN(o.prix) AS prix_min, COUNT(o.id) AS nombre_offres
        FROM livres l
        JOIN categories c ON c.id = l.categorie_id
        LEFT JOIN offres o ON o.livre_id = l.id AND o.statut_moderation = 'publiee'
        WHERE 1=1";
$parametres = [];

if ($recherche) {
    $sql .= " AND (l.titre LIKE ? OR l.auteur LIKE ?)";
    $parametres[] = "%$recherche%";
    $parametres[] = "%$recherche%";
}
if ($categorieSlug) {
    $sql .= " AND c.slug = ?";
    $parametres[] = $categorieSlug;
}

$sql .= " GROUP BY l.id ORDER BY l.date_creation DESC";

$requete = $pdo->prepare($sql);
$requete->execute($parametres);
$livres = $requete->fetchAll();

repondreJson($livres);
