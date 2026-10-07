<?php
// =============================================================================
// ENDPOINT : GET /api/commandes/telecharger_livre.php?commande_id=1&offre_id=2
// RÔLE : télécharge un livre numérique uniquement après paiement réussi.
// Le fichier reste dans le stockage privé et n'est jamais exposé directement.
// =============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/fichiers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

$utilisateur = exigerUtilisateurConnecte($pdo);

$commandeId = $_GET['commande_id'] ?? null;
$offreId = $_GET['offre_id'] ?? null;

if (
    !$commandeId ||
    !filter_var($commandeId, FILTER_VALIDATE_INT) ||
    !$offreId ||
    !filter_var($offreId, FILTER_VALIDATE_INT)
) {
    http_response_code(400);
    exit('Paramètres invalides.');
}

// -----------------------------------------------------------------------------
// Vérifier que la commande appartient à l'utilisateur et qu'elle est payée.
// Vérifier également que l'offre demandée fait bien partie de cette commande.
// -----------------------------------------------------------------------------

$requete = $pdo->prepare(
    "SELECT
        c.id AS commande_id,
        c.statut AS statut_commande,
        o.id AS offre_id,
        o.type,
        o.fichier_numerique,
        l.titre
     FROM commandes c
     JOIN lignes_commande lc ON lc.commande_id = c.id
     JOIN offres o ON o.id = lc.offre_id
     JOIN livres l ON l.id = o.livre_id
     WHERE c.id = ?
       AND c.utilisateur_id = ?
       AND o.id = ?
     LIMIT 1"
);

$requete->execute([
    $commandeId,
    $utilisateur['id'],
    $offreId
]);

$achat = $requete->fetch();

if (!$achat) {
    http_response_code(404);
    exit('Livre acheté introuvable.');
}

// -----------------------------------------------------------------------------
// Le téléchargement est strictement réservé aux commandes payées.
// -----------------------------------------------------------------------------

if ($achat['statut_commande'] !== 'payee') {
    http_response_code(403);
    exit('Le téléchargement est disponible après paiement réussi.');
}

// -----------------------------------------------------------------------------
// Vérifier qu'il s'agit bien d'une offre numérique.
// -----------------------------------------------------------------------------

if ($achat['type'] !== 'numerique') {
    http_response_code(400);
    exit('Cette offre ne correspond pas à un livre numérique.');
}

if (empty($achat['fichier_numerique'])) {
    http_response_code(404);
    exit('Fichier numérique introuvable.');
}

// -----------------------------------------------------------------------------
// Construire le chemin uniquement à partir du nom de fichier enregistré.
// basename() empêche toute tentative de traversée de répertoires.
// -----------------------------------------------------------------------------

$nomFichier = basename($achat['fichier_numerique']);
$cheminFichier = STOCKAGE_LIVRES_NUMERIQUES . '/' . $nomFichier;

if (!is_file($cheminFichier)) {
    http_response_code(404);
    exit('Fichier numérique introuvable dans le stockage.');
}

// -----------------------------------------------------------------------------
// Envoi sécurisé du fichier PDF.
// -----------------------------------------------------------------------------

$nomTelechargement = preg_replace(
    '/[^A-Za-z0-9._-]/',
    '_',
    $achat['titre']
) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($cheminFichier));
header(
    'Content-Disposition: attachment; filename="' .
    $nomTelechargement .
    '"'
);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($cheminFichier);
exit;
