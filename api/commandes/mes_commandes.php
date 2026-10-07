<?php
// =============================================================================
// ENDPOINT : GET /api/commandes/mes_commandes.php
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

activerCors();

$utilisateur = exigerUtilisateurConnecte($pdo);

$requete = $pdo->prepare(
    "SELECT
        c.*,
        p.statut AS statut_paiement,
        p.fournisseur
     FROM commandes c
     LEFT JOIN paiements p ON p.commande_id = c.id
     WHERE c.utilisateur_id = ?
     ORDER BY c.date_creation DESC"
);
$requete->execute([$utilisateur['id']]);

$commandes = $requete->fetchAll();

$requeteLignes = $pdo->prepare(
    "SELECT
        lc.commande_id,
        lc.offre_id,
        lc.quantite,
        lc.prix_unitaire_fige,
        lc.mode_livraison,
        lc.statut_ligne,
        o.type,
        l.titre
     FROM lignes_commande lc
     JOIN offres o ON o.id = lc.offre_id
     JOIN livres l ON l.id = o.livre_id
     WHERE lc.commande_id = ?
     ORDER BY lc.id ASC"
);

foreach ($commandes as &$commande) {
    $requeteLignes->execute([$commande['id']]);
    $commande['lignes'] = $requeteLignes->fetchAll();
}
unset($commande);

repondreJson($commandes);