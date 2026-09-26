<?php
// =============================================================================
// ENDPOINT : GET/POST /api/vendeur/mes_offres.php
// GET  : liste des offres du vendeur connecté
// POST : publier une nouvelle offre (livre existant OU nouveau livre)
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$utilisateur = exigerVendeurActif($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requete = $pdo->prepare(
        "SELECT o.*, l.titre AS livre_titre FROM offres o
         JOIN livres l ON l.id = o.livre_id
         WHERE o.vendeur_id = ? ORDER BY o.date_creation DESC"
    );
    $requete->execute([$utilisateur['id']]);
    repondreJson($requete->fetchAll());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donnees = lireCorpsJson();

    $type = $donnees['type'] ?? '';
    $prix = $donnees['prix'] ?? null;
    $livreId = $donnees['livre_id'] ?? null;
    $livreNouveau = $donnees['livre_nouveau'] ?? null;

    // --- Validation des règles métier (physique vs numérique) ---
    $erreurs = [];
    if (!in_array($type, ['physique', 'numerique'])) $erreurs['type'] = "Type invalide.";
    if (!$prix || $prix <= 0) $erreurs['prix'] = "Prix invalide.";
    if (!$livreId && !$livreNouveau) $erreurs['livre'] = "Livre existant ou nouveau livre requis.";
    if ($livreId && $livreNouveau) $erreurs['livre'] = "Fournissez soit un livre existant, soit un nouveau, pas les deux.";

    $quantiteStock = null; $etatArticle = null; $statutDroits = null; $justificatifDroits = null;

    if ($type === 'physique') {
        $quantiteStock = $donnees['quantite_stock'] ?? null;
        $etatArticle = $donnees['etat_article'] ?? 'neuf';
        if (!$quantiteStock) $erreurs['quantite_stock'] = "Stock requis pour un livre physique.";
    } elseif ($type === 'numerique') {
        $statutDroits = $donnees['statut_droits'] ?? null;
        $justificatifDroits = $donnees['justificatif_droits'] ?? null;
        if (!$statutDroits) $erreurs['statut_droits'] = "Statut des droits requis pour un livre numérique.";
        if ($statutDroits === 'droits_detenus' && !$justificatifDroits) {
            $erreurs['justificatif_droits'] = "Justificatif requis si vous détenez les droits.";
        }
    }

    if (!empty($erreurs)) repondreJson(['erreurs' => $erreurs], 400);

    $pdo->beginTransaction();
    try {
        if ($livreNouveau) {
            if (empty($livreNouveau['titre']) || empty($livreNouveau['auteur']) || empty($livreNouveau['categorie_id'])) {
                repondreJson(['erreurs' => ['livre_nouveau' => 'Titre, auteur et catégorie requis.']], 400);
            }
            $pdo->prepare(
                "INSERT INTO livres (titre, auteur, categorie_id, description) VALUES (?, ?, ?, ?)"
            )->execute([
                $livreNouveau['titre'], $livreNouveau['auteur'],
                $livreNouveau['categorie_id'], $livreNouveau['description'] ?? null,
            ]);
            $livreId = $pdo->lastInsertId();
        }

        $pdo->prepare(
            "INSERT INTO offres (livre_id, vendeur_id, type, prix, quantite_stock, etat_article,
             statut_droits, justificatif_droits, statut_moderation)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')"
        )->execute([
            $livreId, $utilisateur['id'], $type, $prix,
            $quantiteStock, $etatArticle, $statutDroits, $justificatifDroits,
        ]);
        $offreId = $pdo->lastInsertId();

        $pdo->commit();
        repondreJson(['message' => 'Offre soumise, en attente de modération.', 'id' => $offreId], 201);

    } catch (Exception $e) {
        $pdo->rollBack();
        repondreJson(['erreur' => 'Erreur lors de la publication.'], 500);
    }
}

repondreJson(['erreur' => 'Méthode non autorisée.'], 405);
