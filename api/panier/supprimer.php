<?php
// =============================================================================
// ENDPOINT : POST /api/panier/supprimer.php?ligne_id=1
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$utilisateur = exigerUtilisateurConnecte($pdo);
$ligneId = $_GET['ligne_id'] ?? null;
if (!$ligneId) repondreJson(['erreur' => 'ligne_id manquant.'], 400);

$requete = $pdo->prepare(
    "DELETE lp FROM lignes_panier lp
     JOIN paniers p ON p.id = lp.panier_id
     WHERE lp.id = ? AND p.utilisateur_id = ?"
);
$requete->execute([$ligneId, $utilisateur['id']]);
repondreJson(['message' => 'Ligne supprimée.']);
