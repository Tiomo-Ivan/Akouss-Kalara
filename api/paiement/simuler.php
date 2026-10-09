<?php
// =============================================================================
// ENDPOINT DÉSACTIVÉ
// Les paiements simulés sont retirés du projet.
// Aucun paiement ni aucune commande ne doit être modifié par cet endpoint.
// L'intégration officielle sera préparée à partir de la documentation ARITED.
// =============================================================================

require_once __DIR__ . '/../config/utils.php';

activerCors();

repondreJson([
    'erreur' => 'La simulation des paiements est désactivée. '
        . 'Le paiement en ligne sera disponible après l’intégration officielle.'
], 410);
