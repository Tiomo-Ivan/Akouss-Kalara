<?php
// =============================================================================
// ENDPOINT : POST /api/auth/inscription.php
// RÔLE : crée un compte utilisateur (et optionnellement un profil vendeur),
// génère un jeton de vérification par email.
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
$nomComplet = trim($donnees['nom_complet'] ?? '');
$telephone = trim($donnees['telephone'] ?? '');
$devenirVendeur = $donnees['devenir_vendeur'] ?? false;
$nomBoutique = trim($donnees['nom_boutique'] ?? '');

// --- Validation ---
$erreurs = [];
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs['email'] = "Email invalide.";
if (strlen($motDePasse) < 8) $erreurs['mot_de_passe'] = "8 caractères minimum.";
if (!$nomComplet) $erreurs['nom_complet'] = "Le nom complet est requis.";
if ($devenirVendeur && !$nomBoutique) $erreurs['nom_boutique'] = "Le nom de la boutique est requis.";

if (!empty($erreurs)) {
    repondreJson(['erreurs' => $erreurs], 400);
}

// Vérifie l'unicité de l'email
$requete = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
$requete->execute([$email]);
if ($requete->fetch()) {
    repondreJson(['erreurs' => ['email' => 'Cet email est déjà utilisé.']], 400);
}

// --- Création ---
$pdo->beginTransaction();
try {
    $motDePasseHache = password_hash($motDePasse, PASSWORD_DEFAULT);

    $requete = $pdo->prepare(
        "INSERT INTO utilisateurs (email, mot_de_passe, nom_complet, telephone, est_actif)
         VALUES (?, ?, ?, ?, 0)"
    );
    $requete->execute([$email, $motDePasseHache, $nomComplet, $telephone]);
    $utilisateurId = $pdo->lastInsertId();

    // Jeton de vérification du compte
    $jetonCompte = bin2hex(random_bytes(32));
    $pdo->prepare(
        "INSERT INTO jetons_verification (utilisateur_id, jeton, type) VALUES (?, ?, 'activation_compte')"
    )->execute([$utilisateurId, $jetonCompte]);

    if ($devenirVendeur) {
        $pdo->prepare(
            "INSERT INTO profils_vendeur (utilisateur_id, nom_boutique, statut) VALUES (?, ?, 'non_verifie')"
        )->execute([$utilisateurId, $nomBoutique]);

        $jetonVendeur = bin2hex(random_bytes(32));
        $pdo->prepare(
            "INSERT INTO jetons_verification (utilisateur_id, jeton, type) VALUES (?, ?, 'activation_vendeur')"
        )->execute([$utilisateurId, $jetonVendeur]);
    }

    $pdo->commit();

    // NOTE : en développement, on affiche le lien au lieu d'envoyer un vrai
    // email (pas de serveur SMTP configuré sous XAMPP par défaut). En
    // production, remplacer par un vrai envoi (mail() ou un service comme
    // PHPMailer + un serveur SMTP réel).
    $lienActivation = "http://localhost/akouss-kalara-php/web/verifier-email.html?jeton=" . $jetonCompte;

    repondreJson([
        'message' => 'Compte créé. Vérifiez votre email pour l\'activer.',
        'lien_activation_dev' => $lienActivation, // uniquement utile en développement
    ], 201);

} catch (Exception $e) {
    $pdo->rollBack();
    repondreJson(['erreur' => 'Erreur lors de la création du compte.'], 500);
}
