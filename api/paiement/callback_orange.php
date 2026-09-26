<?php
// =============================================================================
// ENDPOINT : GET/POST /api/paiement/callback_orange.php
// RÔLE : reçoit la notification d'Orange Money après une tentative de
// paiement (redirection du client ou notification serveur-à-serveur).
// Cette URL doit être PUBLIQUEMENT accessible en production (pas localhost) —
// utilisez ngrok ou un vrai hébergement pour tester ce flux de bout en bout.
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

$reference = $_GET['ref'] ?? $_POST['ref'] ?? null;
$statutRecu = $_GET['statut'] ?? $_POST['status'] ?? null;

if (!$reference) {
    http_response_code(400);
    exit('Référence manquante.');
}

$requete = $pdo->prepare("SELECT * FROM paiements WHERE reference_transaction = ?");
$requete->execute([$reference]);
$paiement = $requete->fetch();

if ($paiement) {
    $nouveauStatut = ($statutRecu === 'succes' || $statutRecu === 'SUCCESS') ? 'succes' : 'echec';
    $pdo->prepare("UPDATE paiements SET statut = ? WHERE id = ?")->execute([$nouveauStatut, $paiement['id']]);

    if ($nouveauStatut === 'succes') {
        $pdo->prepare("UPDATE commandes SET statut = 'payee' WHERE id = ?")->execute([$paiement['commande_id']]);
    }
}

// Redirige le client vers une page de confirmation côté frontend
header('Location: http://localhost/akouss-kalara-php/web/commande.html?commande_id=' . ($paiement['commande_id'] ?? ''));
exit;
