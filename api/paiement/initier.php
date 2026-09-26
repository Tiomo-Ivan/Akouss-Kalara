<?php
// =============================================================================
// ENDPOINT : POST /api/paiement/initier.php
// Body : {"commande_id": 1, "fournisseur": "mtn_momo", "telephone": "6XXXXXXXX"}
// RÔLE : crée l'enregistrement Paiement et appelle l'API du fournisseur choisi.
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/mtn_momo.php';
require_once __DIR__ . '/orange_money.php';
activerCors();

$utilisateur = exigerUtilisateurConnecte($pdo);
$donnees = lireCorpsJson();

$commandeId = $donnees['commande_id'] ?? null;
$fournisseur = $donnees['fournisseur'] ?? null;
$telephone = $donnees['telephone'] ?? null;

if (!$commandeId || !in_array($fournisseur, ['mtn_momo', 'orange_money']) || !$telephone) {
    repondreJson(['erreur' => 'Données de paiement incomplètes.'], 400);
}

// Vérifie que la commande appartient bien à l'utilisateur et est en attente
$requete = $pdo->prepare(
    "SELECT * FROM commandes WHERE id = ? AND utilisateur_id = ? AND statut = 'en_attente'"
);
$requete->execute([$commandeId, $utilisateur['id']]);
$commande = $requete->fetch();
if (!$commande) repondreJson(['erreur' => 'Commande introuvable ou déjà traitée.'], 404);

// Empêche de créer deux paiements pour la même commande
$requete = $pdo->prepare("SELECT id FROM paiements WHERE commande_id = ?");
$requete->execute([$commandeId]);
if ($requete->fetch()) repondreJson(['erreur' => 'Un paiement existe déjà pour cette commande.'], 400);

$referenceExterne = 'AKOUSS-' . $commandeId . '-' . time();

if ($fournisseur === 'mtn_momo') {
    $resultat = initierPaiementMtn($commande['montant_total'], $telephone, $referenceExterne);
} else {
    $resultat = initierPaiementOrange($commande['montant_total'], $telephone, $referenceExterne);
}

if (!$resultat['succes']) {
    repondreJson(['erreur' => 'Échec de l\'initiation du paiement.', 'detail' => $resultat['erreur'] ?? null], 502);
}

// Enregistre le paiement en base, statut "en_attente" (confirmé par le callback ensuite)
$pdo->prepare(
    "INSERT INTO paiements (commande_id, fournisseur, telephone_paiement, montant, statut, reference_transaction)
     VALUES (?, ?, ?, ?, 'en_attente', ?)"
)->execute([
    $commandeId, $fournisseur, $telephone, $commande['montant_total'],
    $resultat['reference_id'],
]);

$reponse = [
    'message' => 'Paiement initié. Confirmez sur votre téléphone.',
    'reference_id' => $resultat['reference_id'],
];
// Orange Money utilise un modèle de redirection vers une page de paiement
if ($fournisseur === 'orange_money' && isset($resultat['url_paiement'])) {
    $reponse['url_paiement'] = $resultat['url_paiement'];
}

repondreJson($reponse, 201);
