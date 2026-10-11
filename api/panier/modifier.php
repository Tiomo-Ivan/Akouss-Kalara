<?php
// =============================================================================
// ENDPOINT : POST /api/panier/modifier.php
// Body : {"ligne_id": 1, "quantite": 3}
// Modifie une ligne du panier en vérifiant l'utilisateur et le stock.
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

// 3. Lire et valider les données reçues.
$donnees = lireCorpsJson();

$ligneIdBrut = $donnees['ligne_id'] ?? null;
$quantiteBrute = $donnees['quantite'] ?? null;

if (
    !(is_int($ligneIdBrut) || (is_string($ligneIdBrut) && ctype_digit($ligneIdBrut)))
    || !(is_int($quantiteBrute) || (is_string($quantiteBrute) && ctype_digit($quantiteBrute)))
) {
    repondreJson(['erreur' => 'Données invalides.'], 400);
}

$ligneId = (int) $ligneIdBrut;
$quantite = (int) $quantiteBrute;

if ($ligneId < 1 || $quantite < 1 || $quantite > 100) {
    repondreJson(
        ['erreur' => 'La ligne ou la quantité est invalide. La quantité doit être comprise entre 1 et 100.'],
        400
    );
}

// 4. Vérifier le panier, la propriété de la ligne et le stock.
try {
    $pdo->beginTransaction();

    // Verrouiller le panier de l'utilisateur.
    $requetePanier = $pdo->prepare(
        'SELECT id FROM paniers WHERE utilisateur_id = ? FOR UPDATE'
    );
    $requetePanier->execute([$utilisateur['id']]);
    $panier = $requetePanier->fetch(PDO::FETCH_ASSOC);

    if (!$panier) {
        throw new DomainException('Panier introuvable.', 404);
    }

    // Verrouiller la ligne et l'offre correspondante.
    $requeteLigne = $pdo->prepare(
        "SELECT
            lp.id,
            lp.quantite AS ancienne_quantite,
            o.id AS offre_id,
            o.type,
            o.quantite_stock,
            o.statut_moderation
         FROM lignes_panier lp
         JOIN offres o ON o.id = lp.offre_id
         WHERE lp.id = ? AND lp.panier_id = ?
         FOR UPDATE"
    );

    $requeteLigne->execute([$ligneId, $panier['id']]);
    $ligne = $requeteLigne->fetch(PDO::FETCH_ASSOC);

    if (!$ligne) {
        throw new DomainException('Ligne introuvable dans votre panier.', 404);
    }

    // Une offre non publiée ne peut pas être conservée dans le panier.
    if ($ligne['statut_moderation'] !== 'publiee') {
        throw new DomainException('Cette offre n’est plus disponible.', 409);
    }

    // Pour les produits physiques, vérifier le stock disponible.
    if ($ligne['type'] === 'physique') {
        if (
            $ligne['quantite_stock'] === null
            || $quantite > (int) $ligne['quantite_stock']
        ) {
            throw new DomainException(
                'Stock insuffisant pour la quantité demandée.',
                409
            );
        }
    }

    // Mettre à jour uniquement la ligne du panier vérifié.
    $requeteMaj = $pdo->prepare(
        'UPDATE lignes_panier
         SET quantite = ?
         WHERE id = ? AND panier_id = ?'
    );

    $requeteMaj->execute([
        $quantite,
        $ligneId,
        $panier['id']
    ]);

    $pdo->commit();

    repondreJson(['message' => 'Quantité mise à jour.']);

} catch (DomainException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $codeHttp = in_array($e->getCode(), [404, 409], true)
        ? $e->getCode()
        : 400;

    repondreJson(['erreur' => $e->getMessage()], $codeHttp);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erreur modification panier : ' . $e->getMessage());

    repondreJson(
        ['erreur' => 'Une erreur interne est survenue.'],
        500
    );
}
