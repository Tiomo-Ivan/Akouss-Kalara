<?php
// =============================================================================
// ENDPOINT : GET /api/vendeur/livres_existants.php?q=recherche
// RÔLE : recherche de livres existants AVANT publication, pour éviter les
// doublons de fiche Livre. Réservé aux vendeurs actifs.
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$utilisateur = exigerVendeurActif($pdo);

$q = $_GET['q'] ?? '';
if (strlen($q) < 2) repondreJson([]);

$requete = $pdo->prepare(
    "SELECT id, titre, auteur FROM livres WHERE titre LIKE ? OR auteur LIKE ? LIMIT 10"
);
$requete->execute(["%$q%", "%$q%"]);
repondreJson($requete->fetchAll());
