<?php
// =============================================================================
// ENDPOINT : GET /api/admin/vendeurs.php?statut=actif
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

exigerAdmin($pdo);

$statut = $_GET['statut'] ?? null;
$sql = "SELECT pv.*, u.email, u.nom_complet FROM profils_vendeur pv
        JOIN utilisateurs u ON u.id = pv.utilisateur_id WHERE 1=1";
$parametres = [];
if ($statut) { $sql .= " AND pv.statut = ?"; $parametres[] = $statut; }

$requete = $pdo->prepare($sql);
$requete->execute($parametres);
repondreJson($requete->fetchAll());
