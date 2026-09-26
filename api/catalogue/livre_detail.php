<?php
// =============================================================================
// ENDPOINT : GET /api/catalogue/livre_detail.php?id=1
// RÔLE : détails d'un livre avec toutes ses offres publiées.
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$id = $_GET['id'] ?? null;
if (!$id) repondreJson(['erreur' => 'ID manquant.'], 400);

$requete = $pdo->prepare(
    "SELECT l.*, c.nom AS categorie_nom, c.id AS categorie_id
     FROM livres l JOIN categories c ON c.id = l.categorie_id WHERE l.id = ?"
);
$requete->execute([$id]);
$livre = $requete->fetch();
if (!$livre) repondreJson(['erreur' => 'Livre introuvable.'], 404);

$requeteOffres = $pdo->prepare(
    "SELECT o.*, u.nom_complet AS vendeur_nom FROM offres o
     JOIN utilisateurs u ON u.id = o.vendeur_id
     WHERE o.livre_id = ? AND o.statut_moderation = 'publiee'
     ORDER BY o.prix ASC"
);
$requeteOffres->execute([$id]);
$livre['offres'] = $requeteOffres->fetchAll();

repondreJson($livre);
