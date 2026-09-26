<?php
// =============================================================================
// ENDPOINT : POST /api/admin/reactiver_vendeur.php?id=1
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

exigerAdmin($pdo);
$id = $_GET['id'] ?? null;
if (!$id) repondreJson(['erreur' => 'ID manquant.'], 400);

$pdo->prepare("UPDATE profils_vendeur SET statut = 'actif', motif_suspension = NULL WHERE id = ?")
    ->execute([$id]);

repondreJson(['message' => 'Vendeur réactivé.']);
