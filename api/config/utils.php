<?php
// =============================================================================
// RÔLE DE CE FICHIER : fonctions utilitaires réutilisées par tous les
// endpoints de l'API — réponses JSON standardisées, CORS, et vérification
// du jeton de session (équivalent simplifié d'un JWT).
// =============================================================================

/**
 * Active les en-têtes CORS pour autoriser le frontend (HTML/JS servi
 * séparément) à appeler cette API sans être bloqué par le navigateur.
 * À appeler en tout début de chaque fichier endpoint.
 */
function activerCors() {
    header('Access-Control-Allow-Origin: *'); // en développement ; à restreindre en production
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');

    // Le navigateur envoie une requête OPTIONS de "pré-vérification" avant
    // certaines requêtes (ex: POST avec JSON) — on y répond immédiatement.
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/** Envoie une réponse JSON avec le code HTTP donné, puis arrête le script. */
function repondreJson($donnees, $codeHttp = 200) {
    http_response_code($codeHttp);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Lit le corps JSON de la requête entrante et le décode en tableau PHP. */
function lireCorpsJson() {
    $corps = file_get_contents('php://input');
    return json_decode($corps, true) ?? [];
}

/**
 * Vérifie le jeton envoyé dans l'en-tête "Authorization: Bearer <jeton>".
 * Retourne les infos de l'utilisateur connecté, ou envoie une erreur 401
 * et arrête le script si le jeton est absent/invalide/expiré.
 */
function exigerUtilisateurConnecte($pdo) {
    $entetes = getallheaders();
    $autorisation = $entetes['Authorization'] ?? $entetes['authorization'] ?? '';

    if (!preg_match('/Bearer\s+(\S+)/', $autorisation, $correspondances)) {
        repondreJson(['erreur' => 'Authentification requise.'], 401);
    }
    $jeton = $correspondances[1];

    $requete = $pdo->prepare(
        "SELECT u.* FROM sessions s
         JOIN utilisateurs u ON u.id = s.utilisateur_id
         WHERE s.jeton = ? AND s.date_expiration > NOW()"
    );
    $requete->execute([$jeton]);
    $utilisateur = $requete->fetch();

    if (!$utilisateur) {
        repondreJson(['erreur' => 'Session invalide ou expirée. Reconnectez-vous.'], 401);
    }

    return $utilisateur;
}

/** Comme exigerUtilisateurConnecte, mais exige en plus un profil vendeur actif. */
function exigerVendeurActif($pdo) {
    $utilisateur = exigerUtilisateurConnecte($pdo);

    $requete = $pdo->prepare("SELECT * FROM profils_vendeur WHERE utilisateur_id = ? AND statut = 'actif'");
    $requete->execute([$utilisateur['id']]);
    $profil = $requete->fetch();

    if (!$profil) {
        repondreJson(['erreur' => 'Statut vendeur actif requis.'], 403);
    }

    $utilisateur['profil_vendeur'] = $profil;
    return $utilisateur;
}

/** Comme exigerUtilisateurConnecte, mais exige en plus les droits administrateur. */
function exigerAdmin($pdo) {
    $utilisateur = exigerUtilisateurConnecte($pdo);
    if (!$utilisateur['est_admin']) {
        repondreJson(['erreur' => 'Action réservée aux administrateurs.'], 403);
    }
    return $utilisateur;
}
