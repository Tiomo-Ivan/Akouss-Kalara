<?php
// =============================================================================
// ENDPOINT : POST /api/admin/moderer_offre.php?id=1
// Body : {"decision": "publiee"} ou {"decision": "refusee"}
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/fichiers.php';
activerCors();

exigerAdmin($pdo);
$id = $_GET['id'] ?? null;
$donnees = lireCorpsJson();
$decision = $donnees['decision'] ?? null;

if (!$id || !in_array($decision, ['publiee', 'refusee'])) {
    repondreJson(['erreur' => 'Données invalides.'], 400);
}

$requete = $pdo->prepare("SELECT * FROM offres WHERE id = ?");
$requete->execute([$id]);
$offre = $requete->fetch();
if (!$offre) repondreJson(['erreur' => 'Offre introuvable.'], 404);

if ($decision === 'publiee' && $offre['type'] === 'numerique') {
    if (empty($offre['fichier_numerique'])) {
        repondreJson(['erreur' => 'Fichier numérique manquant, publication impossible.'], 400);
    }

    $cheminFichier = STOCKAGE_LIVRES_NUMERIQUES . '/' . basename($offre['fichier_numerique']);

    if (!is_file($cheminFichier)) {
        repondreJson(['erreur' => 'Fichier numérique introuvable dans le stockage, publication impossible.'], 400);
    }

    if ($offre['statut_droits'] === 'droits_detenus' && !$offre['justificatif_droits']) {
        repondreJson(['erreur' => 'Justificatif de droits manquant, publication impossible.'], 400);
    }
}

$pdo->prepare("UPDATE offres SET statut_moderation = ? WHERE id = ?")->execute([$decision, $id]);
repondreJson(['message' => $decision === 'publiee' ? 'Offre publiée.' : 'Offre refusée.']);
