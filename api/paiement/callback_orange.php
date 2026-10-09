<?php
// =============================================================================
// CALLBACK ORANGE MONEY TEMPORAIREMENT DÉSACTIVÉ
//
// La confirmation des paiements est suspendue jusqu'à l'intégration du
// mécanisme officiel de vérification fourni par ARITED.
//
// Un retour navigateur ou un paramètre GET/POST ne constitue pas une preuve
// de paiement. Cet endpoint ne modifie aucune commande ni aucun paiement.
// =============================================================================

http_response_code(503);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

echo 'La confirmation Orange Money est temporairement indisponible. '
    . 'La vérification officielle du paiement sera intégrée avec ARITED.';
