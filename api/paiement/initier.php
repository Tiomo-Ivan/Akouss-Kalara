<?php
// =============================================================================
// INITIALISATION DES PAIEMENTS TEMPORAIREMENT DÉSACTIVÉE
//
// Les intégrations MTN MoMo et Orange Money restent en attente de la
// documentation officielle et des paramètres fournis par ARITED.
//
// Aucun appel fournisseur et aucune modification de la base de données
// ne sont effectués par cet endpoint pendant cette période.
// =============================================================================

http_response_code(503);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'erreur' => 'Le paiement en ligne est temporairement indisponible. '
        . 'L’intégration officielle avec ARITED est en attente.'
], JSON_UNESCAPED_UNICODE);
