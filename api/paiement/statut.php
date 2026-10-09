<?php
// =============================================================================
// CONSULTATION DU STATUT DES PAIEMENTS TEMPORAIREMENT DÉSACTIVÉE
//
// La vérification officielle des transactions MTN MoMo et Orange Money
// attend la documentation et les paramètres fournis par ARITED.
//
// Cet endpoint ne contacte aucun fournisseur et ne modifie aucune donnée.
// Les paiements historiques sont conservés tels quels.
// =============================================================================

http_response_code(503);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'erreur' => 'La vérification des paiements est temporairement indisponible. '
        . 'La validation officielle sera intégrée avec ARITED.'
], JSON_UNESCAPED_UNICODE);
