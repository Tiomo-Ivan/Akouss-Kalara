<?php
// =============================================================================
// MODE SIMULATION — développement uniquement
// Simule un paiement MTN MoMo ou Orange Money sans appeler leur API.
// À remplacer/retirer lorsque l'intégration ARITED sera disponible.
// =============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

activerCors();

$utilisateur = exigerUtilisateurConnecte($pdo);
$donnees = lireCorpsJson();

$commandeId = $donnees['commande_id'] ?? null;
$fournisseur = $donnees['fournisseur'] ?? null;
$telephone = $donnees['telephone'] ?? null;
$statutDemande = $donnees['statut'] ?? null;

if (
    !$commandeId ||
    !in_array($fournisseur, ['mtn_momo', 'orange_money'], true) ||
    !$telephone ||
    !in_array($statutDemande, ['succes', 'echec'], true)
) {
    repondreJson([
        'erreur' => 'Données de simulation incomplètes.'
    ], 400);
}

// Vérifier que la commande appartient bien à l'utilisateur
$requete = $pdo->prepare(
    "SELECT * FROM commandes
     WHERE id = ? AND utilisateur_id = ?"
);
$requete->execute([$commandeId, $utilisateur['id']]);
$commande = $requete->fetch();

if (!$commande) {
    repondreJson([
        'erreur' => 'Commande introuvable.'
    ], 404);
}

if ($commande['statut'] !== 'en_attente') {
    repondreJson([
        'erreur' => 'Cette commande a déjà été traitée.'
    ], 400);
}

// Vérifier qu'il n'existe pas déjà de paiement
$requete = $pdo->prepare(
    "SELECT id FROM paiements WHERE commande_id = ?"
);
$requete->execute([$commandeId]);

if ($requete->fetch()) {
    repondreJson([
        'erreur' => 'Un paiement existe déjà pour cette commande.'
    ], 400);
}

$reference = 'SIM-AKOUSS-' . $commandeId . '-' . strtoupper(
    bin2hex(random_bytes(4))
);

try {
    $pdo->beginTransaction();

    $requete = $pdo->prepare(
        "INSERT INTO paiements
        (
            commande_id,
            fournisseur,
            telephone_paiement,
            montant,
            statut,
            reference_transaction
        )
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    $statutPaiement = $statutDemande === 'succes'
        ? 'succes'
        : 'echec';

    $requete->execute([
        $commandeId,
        $fournisseur,
        $telephone,
        $commande['montant_total'],
        $statutPaiement,
        $reference
    ]);

    if ($statutDemande === 'succes') {
        $requete = $pdo->prepare(
            "UPDATE commandes
             SET statut = 'payee'
             WHERE id = ?"
        );
        $requete->execute([$commandeId]);
    }

    $pdo->commit();

    repondreJson([
        'message' => $statutDemande === 'succes'
            ? 'Paiement simulé avec succès.'
            : 'Échec de paiement simulé.',
        'commande_id' => (int) $commandeId,
        'montant' => $commande['montant_total'],
        'fournisseur' => $fournisseur,
        'statut_paiement' => $statutPaiement,
        'statut_commande' => $statutDemande === 'succes'
            ? 'payee'
            : 'en_attente',
        'reference_id' => $reference
    ], 201);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    repondreJson([
        'erreur' => 'Impossible de simuler le paiement.'
    ], 500);
}
