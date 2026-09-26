<?php
// =============================================================================
// ENDPOINT : POST /api/admin/suspendre_vendeur.php?id=1
// Body : {"motif": "raison"}
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

exigerAdmin($pdo);
$id = $_GET['id'] ?? null;
$donnees = lireCorpsJson();
$motif = trim($donnees['motif'] ?? '');

if (!$id) repondreJson(['erreur' => 'ID manquant.'], 400);
if (!$motif) repondreJson(['erreur' => 'Un motif est requis.'], 400);

$pdo->prepare("UPDATE profils_vendeur SET statut = 'suspendu', motif_suspension = ? WHERE id = ?")
    ->execute([$motif, $id]);

repondreJson(['message' => 'Vendeur suspendu.']);
