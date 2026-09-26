<?php
// =============================================================================
// ENDPOINT : GET /api/admin/offres_a_moderer.php?statut=en_attente
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

exigerAdmin($pdo);
$statut = $_GET['statut'] ?? 'en_attente';

$requete = $pdo->prepare(
    "SELECT o.*, l.titre AS livre_titre, u.nom_complet AS vendeur_nom
     FROM offres o JOIN livres l ON l.id = o.livre_id JOIN utilisateurs u ON u.id = o.vendeur_id
     WHERE o.statut_moderation = ? ORDER BY o.date_creation ASC"
);
$requete->execute([$statut]);
repondreJson($requete->fetchAll());
