<?php
// =============================================================================
// ENDPOINT : GET /api/auth/verifier_email.php?jeton=xxx
// RÔLE : active le compte (ou le statut vendeur) via le jeton reçu par email.
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

activerCors();

$jeton = $_GET['jeton'] ?? '';
if (!$jeton) {
    repondreJson(['erreur' => 'Jeton manquant.'], 400);
}

$requete = $pdo->prepare(
    "SELECT * FROM jetons_verification WHERE jeton = ? AND est_utilise = 0
     AND date_creation > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
);
$requete->execute([$jeton]);
$jetonInfo = $requete->fetch();

if (!$jetonInfo) {
    repondreJson(['erreur' => 'Ce lien est invalide ou a expiré.'], 400);
}

$pdo->beginTransaction();
try {
    if ($jetonInfo['type'] === 'activation_compte') {
        $pdo->prepare("UPDATE utilisateurs SET est_actif = 1 WHERE id = ?")
            ->execute([$jetonInfo['utilisateur_id']]);
        $message = "Compte activé ! Vous pouvez maintenant vous connecter.";
    } else { // activation_vendeur
        $pdo->prepare("UPDATE profils_vendeur SET statut = 'actif', date_activation = NOW() WHERE utilisateur_id = ?")
            ->execute([$jetonInfo['utilisateur_id']]);
        $message = "Statut vendeur activé !";
    }

    $pdo->prepare("UPDATE jetons_verification SET est_utilise = 1 WHERE id = ?")
        ->execute([$jetonInfo['id']]);

    $pdo->commit();
    repondreJson(['message' => $message]);

} catch (Exception $e) {
    $pdo->rollBack();
    repondreJson(['erreur' => 'Erreur lors de l\'activation.'], 500);
}
