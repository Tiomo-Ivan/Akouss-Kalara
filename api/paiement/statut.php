<?php
// =============================================================================
// ENDPOINT : GET /api/paiement/statut.php?commande_id=1
// RÔLE : le frontend interroge cet endpoint (polling toutes les 3-5 secondes)
// pour savoir si le paiement a été confirmé — utile en attendant que le
// client confirme sur son téléphone après l'initiation.
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/mtn_momo.php';
activerCors();

$utilisateur = exigerUtilisateurConnecte($pdo);
$commandeId = $_GET['commande_id'] ?? null;
if (!$commandeId) repondreJson(['erreur' => 'commande_id manquant.'], 400);

$requete = $pdo->prepare(
    "SELECT p.*, c.utilisateur_id FROM paiements p
     JOIN commandes c ON c.id = p.commande_id
     WHERE p.commande_id = ? AND c.utilisateur_id = ?"
);
$requete->execute([$commandeId, $utilisateur['id']]);
$paiement = $requete->fetch();
if (!$paiement) repondreJson(['erreur' => 'Paiement introuvable.'], 404);

// Si le paiement est encore en attente ET que c'est du MTN MoMo, on
// interroge activement l'API pour voir si le statut a changé (MTN ne
// notifie pas toujours via callback en sandbox — l'interrogation directe
// est le filet de sécurité). Pour Orange Money, le statut est mis à jour
// uniquement par le callback (voir callback_orange.php).
if ($paiement['statut'] === 'en_attente' && $paiement['fournisseur'] === 'mtn_momo') {
    $resultatMtn = verifierStatutMtn($paiement['reference_transaction']);

    if ($resultatMtn['statut'] === 'SUCCESSFUL') {
        $pdo->prepare("UPDATE paiements SET statut = 'succes' WHERE id = ?")->execute([$paiement['id']]);
        $pdo->prepare("UPDATE commandes SET statut = 'payee' WHERE id = ?")->execute([$commandeId]);
        $paiement['statut'] = 'succes';
    } elseif ($resultatMtn['statut'] === 'FAILED') {
        $pdo->prepare("UPDATE paiements SET statut = 'echec' WHERE id = ?")->execute([$paiement['id']]);
        $paiement['statut'] = 'echec';
    }
}

repondreJson(['statut' => $paiement['statut']]);
