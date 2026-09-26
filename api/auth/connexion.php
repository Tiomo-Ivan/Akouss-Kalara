<?php
// =============================================================================
// ENDPOINT : POST /api/auth/connexion.php
// RÔLE : vérifie email/mot de passe, crée une session (jeton), la retourne.
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

activerCors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    repondreJson(['erreur' => 'Méthode non autorisée.'], 405);
}

$donnees = lireCorpsJson();
$email = trim($donnees['email'] ?? '');
$motDePasse = $donnees['mot_de_passe'] ?? '';

$requete = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
$requete->execute([$email]);
$utilisateur = $requete->fetch();

if (!$utilisateur || !password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
    repondreJson(['erreur' => 'Email ou mot de passe incorrect.'], 401);
}

if (!$utilisateur['est_actif']) {
    repondreJson(['erreur' => 'Compte non activé. Vérifiez votre email.'], 403);
}

// Génère un nouveau jeton de session, valable 7 jours
$jeton = bin2hex(random_bytes(32));
$pdo->prepare(
    "INSERT INTO sessions (utilisateur_id, jeton, date_expiration) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))"
)->execute([$utilisateur['id'], $jeton]);

// Vérifie si l'utilisateur a un profil vendeur actif (utile pour l'affichage frontend)
$requeteVendeur = $pdo->prepare("SELECT statut FROM profils_vendeur WHERE utilisateur_id = ?");
$requeteVendeur->execute([$utilisateur['id']]);
$profilVendeur = $requeteVendeur->fetch();

repondreJson([
    'jeton' => $jeton,
    'utilisateur' => [
        'id' => $utilisateur['id'],
        'email' => $utilisateur['email'],
        'nom_complet' => $utilisateur['nom_complet'],
        'est_admin' => (bool) $utilisateur['est_admin'],
        'est_vendeur_actif' => $profilVendeur && $profilVendeur['statut'] === 'actif',
    ],
]);
