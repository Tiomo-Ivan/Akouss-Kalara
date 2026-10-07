<?php
// =============================================================================
// ENDPOINT : POST /api/vendeur/modifier_stock.php
// RÔLE : permet au vendeur actif de modifier le stock d'une offre physique.
// =============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

activerCors();

$utilisateur = exigerVendeurActif($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    repondreJson(['erreur' => 'Méthode non autorisée.'], 405);
}

$donnees = lireCorpsJson();

$offreId = $donnees['offre_id'] ?? null;
$quantiteStock = $donnees['quantite_stock'] ?? null;

$erreurs = [];

if (!is_numeric($offreId) || (int) $offreId <= 0) {
    $erreurs['offre_id'] = 'Identifiant de l’offre invalide.';
}

if (
    $quantiteStock === null ||
    filter_var($quantiteStock, FILTER_VALIDATE_INT) === false ||
    (int) $quantiteStock < 0
) {
    $erreurs['quantite_stock'] =
        'La quantité en stock doit être un nombre entier supérieur ou égal à 0.';
}

if (!empty($erreurs)) {
    repondreJson(['erreurs' => $erreurs], 400);
}

$offreId = (int) $offreId;
$quantiteStock = (int) $quantiteStock;

$requete = $pdo->prepare(
    "SELECT id, type
     FROM offres
     WHERE id = ? AND vendeur_id = ?"
);
$requete->execute([$offreId, $utilisateur['id']]);
$offre = $requete->fetch();

if (!$offre) {
    repondreJson(['erreur' => 'Offre introuvable.'], 404);
}

if ($offre['type'] !== 'physique') {
    repondreJson([
        'erreur' => 'Le stock ne peut être modifié que pour une offre physique.'
    ], 400);
}

$requete = $pdo->prepare(
    "UPDATE offres
     SET quantite_stock = ?
     WHERE id = ? AND vendeur_id = ? AND type = 'physique'"
);
$requete->execute([
    $quantiteStock,
    $offreId,
    $utilisateur['id']
]);

repondreJson([
    'message' => 'Stock mis à jour.',
    'offre_id' => $offreId,
    'quantite_stock' => $quantiteStock
]);
