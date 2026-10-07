<?php
// =============================================================================
// ENDPOINT : GET / POST / PUT / PATCH /api/vendeur/modifier_boutique.php
// Permet au vendeur actif de consulter et modifier les informations
// de sa boutique.
// =============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

activerCors();

$utilisateur = exigerVendeurActif($pdo);

/*
 * GET : récupérer les informations actuelles de la boutique.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requete = $pdo->prepare(
        "SELECT id, utilisateur_id, nom_boutique, description_boutique,
                statut, numero_mobile_money, operateur_mobile_money,
                date_creation, date_activation
         FROM profils_vendeur
         WHERE utilisateur_id = ? AND statut = 'actif'"
    );
    $requete->execute([$utilisateur['id']]);
    $profil = $requete->fetch();

    if (!$profil) {
        repondreJson(['erreur' => 'Profil vendeur actif introuvable.'], 404);
    }

    repondreJson(['boutique' => $profil]);
}

/*
 * Modification : POST, PUT ou PATCH.
 */
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'], true)) {
    repondreJson(['erreur' => 'Méthode non autorisée.'], 405);
}

$donnees = lireCorpsJson();

$nomBoutique = trim($donnees['nom_boutique'] ?? '');
$description = trim($donnees['description_boutique'] ?? '');
$numeroMobileMoney = trim($donnees['numero_mobile_money'] ?? '');
$operateurMobileMoney = $donnees['operateur_mobile_money'] ?? '';

$erreurs = [];

if ($nomBoutique === '') {
    $erreurs['nom_boutique'] = 'Le nom de la boutique est requis.';
} elseif (mb_strlen($nomBoutique) > 255) {
    $erreurs['nom_boutique'] =
        'Le nom de la boutique ne doit pas dépasser 255 caractères.';
}

if (mb_strlen($description) > 5000) {
    $erreurs['description_boutique'] =
        'La description ne doit pas dépasser 5000 caractères.';
}

if (
    $numeroMobileMoney !== '' &&
    !preg_match('/^[0-9]{8,20}$/', $numeroMobileMoney)
) {
    $erreurs['numero_mobile_money'] =
        'Le numéro Mobile Money doit contenir uniquement des chiffres.';
}

if (
    $operateurMobileMoney !== '' &&
    !in_array($operateurMobileMoney, ['mtn_momo', 'orange_money'], true)
) {
    $erreurs['operateur_mobile_money'] =
        'Opérateur Mobile Money invalide.';
}

if ($numeroMobileMoney !== '' && $operateurMobileMoney === '') {
    $erreurs['operateur_mobile_money'] =
        'L’opérateur Mobile Money est requis si un numéro est renseigné.';
}

if ($numeroMobileMoney === '' && $operateurMobileMoney !== '') {
    $erreurs['numero_mobile_money'] =
        'Le numéro Mobile Money est requis si un opérateur est renseigné.';
}

if (!empty($erreurs)) {
    repondreJson(['erreurs' => $erreurs], 400);
}

$requete = $pdo->prepare(
    "UPDATE profils_vendeur
     SET nom_boutique = ?,
         description_boutique = ?,
         numero_mobile_money = ?,
         operateur_mobile_money = ?
     WHERE utilisateur_id = ? AND statut = 'actif'"
);

$requete->execute([
    $nomBoutique,
    $description !== '' ? $description : null,
    $numeroMobileMoney !== '' ? $numeroMobileMoney : null,
    $operateurMobileMoney !== '' ? $operateurMobileMoney : null,
    $utilisateur['id'],
]);

$requeteProfil = $pdo->prepare(
    "SELECT id, utilisateur_id, nom_boutique, description_boutique,
            statut, numero_mobile_money, operateur_mobile_money,
            date_creation, date_activation
     FROM profils_vendeur
     WHERE utilisateur_id = ? AND statut = 'actif'"
);
$requeteProfil->execute([$utilisateur['id']]);
$profil = $requeteProfil->fetch();

if (!$profil) {
    repondreJson(['erreur' => 'Profil vendeur actif introuvable.'], 404);
}

repondreJson([
    'message' => 'Les informations de la boutique ont été mises à jour.',
    'boutique' => $profil,
]);
