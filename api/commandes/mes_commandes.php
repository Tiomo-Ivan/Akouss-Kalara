<?php
// =============================================================================
// ENDPOINT : GET /api/commandes/mes_commandes.php
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$utilisateur = exigerUtilisateurConnecte($pdo);

$requete = $pdo->prepare(
    "SELECT c.*, p.statut AS statut_paiement, p.fournisseur
     FROM commandes c LEFT JOIN paiements p ON p.commande_id = c.id
     WHERE c.utilisateur_id = ? ORDER BY c.date_creation DESC"
);
$requete->execute([$utilisateur['id']]);
repondreJson($requete->fetchAll());
