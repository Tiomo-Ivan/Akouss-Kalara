<?php
// =============================================================================
// ENDPOINT : POST /api/vendeur/retirer_offre.php?id=1
// RÔLE : retire une offre de la vente (le vendeur ne peut retirer que les siennes)
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$utilisateur = exigerVendeurActif($pdo);
$id = $_GET['id'] ?? null;
if (!$id) repondreJson(['erreur' => 'ID manquant.'], 400);

$requete = $pdo->prepare("SELECT id FROM offres WHERE id = ? AND vendeur_id = ?");
$requete->execute([$id, $utilisateur['id']]);
if (!$requete->fetch()) repondreJson(['erreur' => 'Offre introuvable.'], 404);

$pdo->prepare("UPDATE offres SET statut_moderation = 'refusee' WHERE id = ?")->execute([$id]);
repondreJson(['message' => 'Offre retirée de la vente.']);
