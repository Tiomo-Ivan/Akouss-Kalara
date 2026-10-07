<?php
// =============================================================================
// ENDPOINT : POST /api/vendeur/publier_offre_numerique.php
// Crée une offre numérique avec upload sécurisé du fichier PDF.
// =============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/fichiers.php';

activerCors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    repondreJson(['erreur' => 'Méthode non autorisée.'], 405);
}

$utilisateur = exigerVendeurActif($pdo);

// -----------------------------------------------------------------------------
// Lecture des champs multipart/form-data
// -----------------------------------------------------------------------------

$livreId = $_POST['livre_id'] ?? null;
$prix = $_POST['prix'] ?? null;
$statutDroits = $_POST['statut_droits'] ?? null;
$justificatifDroits = $_POST['justificatif_droits'] ?? null;

// -----------------------------------------------------------------------------
// Validation de l'offre
// -----------------------------------------------------------------------------

$erreurs = [];

if (!$prix || !is_numeric($prix) || $prix <= 0) {
    $erreurs['prix'] = 'Prix invalide.';
}

if (!$livreId || !filter_var($livreId, FILTER_VALIDATE_INT)) {
    $erreurs['livre_id'] = 'Livre existant requis pour une offre numérique.';
}

if (!in_array($statutDroits, ['domaine_public', 'droits_detenus'], true)) {
    $erreurs['statut_droits'] = 'Statut des droits invalide.';
}

if ($statutDroits === 'droits_detenus' && !$justificatifDroits) {
    $erreurs['justificatif_droits'] = 'Justificatif requis si vous détenez les droits.';
}

// -----------------------------------------------------------------------------
// Validation du fichier
// -----------------------------------------------------------------------------

if (!isset($_FILES['fichier_numerique'])) {
    $erreurs['fichier_numerique'] = 'Le fichier numérique est requis.';
} else {
    $fichier = $_FILES['fichier_numerique'];

    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        $erreurs['fichier_numerique'] = 'Erreur lors de l’envoi du fichier.';
    } elseif ($fichier['size'] <= 0) {
        $erreurs['fichier_numerique'] = 'Le fichier est vide.';
    } elseif ($fichier['size'] > TAILLE_MAX_LIVRE_NUMERIQUE) {
        $erreurs['fichier_numerique'] = 'Le fichier dépasse la taille maximale de 20 Mo.';
    } else {
        $nomOriginal = $fichier['name'];
        $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));

        if (!in_array($extension, EXTENSIONS_LIVRE_NUMERIQUE, true)) {
            $erreurs['fichier_numerique'] = 'Seuls les fichiers PDF sont autorisés.';
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($fichier['tmp_name']);

        if (!in_array($mime, MIMES_LIVRE_NUMERIQUE, true)) {
            $erreurs['fichier_numerique'] = 'Le fichier envoyé n’est pas un PDF valide.';
        }
    }
}

if (!empty($erreurs)) {
    repondreJson(['erreurs' => $erreurs], 400);
}

// -----------------------------------------------------------------------------
// Vérification du livre
// -----------------------------------------------------------------------------

$requeteLivre = $pdo->prepare("SELECT id FROM livres WHERE id = ?");
$requeteLivre->execute([$livreId]);

if (!$requeteLivre->fetch()) {
    repondreJson(['erreurs' => ['livre_id' => 'Livre introuvable.']], 404);
}

// -----------------------------------------------------------------------------
// Préparation du stockage
// -----------------------------------------------------------------------------

if (!is_dir(STOCKAGE_LIVRES_NUMERIQUES)) {
    if (!mkdir(STOCKAGE_LIVRES_NUMERIQUES, 0750, true)) {
        repondreJson(['erreur' => 'Impossible de créer le dossier de stockage.'], 500);
    }
}

$nomFichier = bin2hex(random_bytes(32)) . '.pdf';
$cheminFichier = STOCKAGE_LIVRES_NUMERIQUES . '/' . $nomFichier;

// -----------------------------------------------------------------------------
// Déplacement du fichier puis création de l'offre
// -----------------------------------------------------------------------------

if (!move_uploaded_file($fichier['tmp_name'], $cheminFichier)) {
    repondreJson(['erreur' => 'Impossible d’enregistrer le fichier numérique.'], 500);
}

try {
    $requete = $pdo->prepare(
        "INSERT INTO offres (
            livre_id,
            vendeur_id,
            type,
            prix,
            quantite_stock,
            etat_article,
            fichier_numerique,
            statut_droits,
            justificatif_droits,
            statut_moderation
        ) VALUES (?, ?, 'numerique', ?, NULL, NULL, ?, ?, ?, 'en_attente')"
    );

    $requete->execute([
        $livreId,
        $utilisateur['id'],
        $prix,
        $nomFichier,
        $statutDroits,
        $justificatifDroits,
    ]);

    $offreId = $pdo->lastInsertId();

    repondreJson([
        'message' => 'Offre numérique soumise, en attente de modération.',
        'id' => $offreId,
    ], 201);

} catch (Exception $e) {
    // Si la base échoue, on supprime le fichier déjà enregistré.
    if (is_file($cheminFichier)) {
        unlink($cheminFichier);
    }

    repondreJson(['erreur' => 'Erreur lors de la création de l’offre.'], 500);
}
