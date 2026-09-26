<?php
// =============================================================================
// ENDPOINT : POST /api/panier/modifier.php
// Body : {"ligne_id": 1, "quantite": 3}
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$utilisateur = exigerUtilisateurConnecte($pdo);
$donnees = lireCorpsJson();
$ligneId = $donnees['ligne_id'] ?? null;
$quantite = (int)($donnees['quantite'] ?? 0);

if (!$ligneId || $quantite < 1) repondreJson(['erreur' => 'Données invalides.'], 400);

// Vérifie que la ligne appartient bien au panier de l'utilisateur connecté
$requete = $pdo->prepare(
    "SELECT lp.id FROM lignes_panier lp
     JOIN paniers p ON p.id = lp.panier_id
     WHERE lp.id = ? AND p.utilisateur_id = ?"
);
$requete->execute([$ligneId, $utilisateur['id']]);
if (!$requete->fetch()) repondreJson(['erreur' => 'Ligne introuvable.'], 404);

$pdo->prepare("UPDATE lignes_panier SET quantite = ? WHERE id = ?")->execute([$quantite, $ligneId]);
repondreJson(['message' => 'Quantité mise à jour.']);
