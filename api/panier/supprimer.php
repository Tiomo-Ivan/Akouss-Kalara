<?php
// =============================================================================
// ENDPOINT : POST /api/panier/supprimer.php?ligne_id=1
// Supprime uniquement une ligne appartenant au panier de l'utilisateur connecté.
// =============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

activerCors();

// 1. Vérifier la méthode HTTP.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST, OPTIONS');
    repondreJson(['erreur' => 'Méthode non autorisée.'], 405);
}

// 2. Vérifier la connexion de l'utilisateur.
$utilisateur = exigerUtilisateurConnecte($pdo);

// 3. Valider strictement l'identifiant de la ligne.
$ligneIdBrut = $_GET['ligne_id'] ?? null;

if (
    !(is_int($ligneIdBrut) || (is_string($ligneIdBrut) && ctype_digit($ligneIdBrut)))
) {
    repondreJson(['erreur' => 'Identifiant de ligne invalide.'], 400);
}

$ligneId = (int) $ligneIdBrut;

if ($ligneId < 1) {
    repondreJson(['erreur' => 'Identifiant de ligne invalide.'], 400);
}

// 4. Supprimer uniquement la ligne appartenant à cet utilisateur.
try {
    $requete = $pdo->prepare(
        "DELETE lp
         FROM lignes_panier lp
         JOIN paniers p ON p.id = lp.panier_id
         WHERE lp.id = ? AND p.utilisateur_id = ?"
    );

    $requete->execute([$ligneId, $utilisateur['id']]);

    if ($requete->rowCount() !== 1) {
        repondreJson(['erreur' => 'Ligne introuvable dans votre panier.'], 404);
    }

    repondreJson(['message' => 'Ligne supprimée.']);

} catch (Throwable $e) {
    error_log('Erreur suppression panier : ' . $e->getMessage());

    repondreJson(
        ['erreur' => 'Une erreur interne est survenue.'],
        500
    );
}
