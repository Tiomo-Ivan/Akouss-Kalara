<?php
// =============================================================================
// RÔLE DE CE FICHIER : fonctions d'intégration avec l'API Orange Money
// (Web Payment API), suivant le flux standard :
//   1. Obtenir un jeton d'accès OAuth (client_id + client_secret)
//   2. Initier le paiement (le client confirme via son téléphone ou via
//      une redirection vers la page de paiement Orange selon l'intégration)
//   3. Vérifier le statut via le webhook de notification ou en interrogeant l'API
//
// ⚠️ NOTE IMPORTANTE : l'API Orange Money varie davantage selon les pays et
// les intégrateurs que celle de MTN. Le code ci-dessous suit le schéma
// général documenté publiquement, mais DOIT être ajusté selon la
// documentation précise fournie par ARITED (noms de champs, URL exactes).
// =============================================================================
require_once __DIR__ . '/../config/paiement_config.php';

function requeteHttpOrange($methode, $url, $entetes = [], $corps = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $methode);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $entetes);
    if ($corps !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($corps) ? http_build_query($corps) : $corps);
    }
    $reponse = curl_exec($ch);
    $codeHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $codeHttp, 'donnees' => json_decode($reponse, true)];
}

/** Étape 1 : obtient un jeton d'accès OAuth (Client Credentials). */
function obtenirJetonAccesOrange() {
    $identifiants = base64_encode(ORANGE_MONEY_CLIENT_ID . ':' . ORANGE_MONEY_CLIENT_SECRET);
    $resultat = requeteHttpOrange('POST', 'https://api.orange.com/oauth/v3/token', [
        'Authorization: Basic ' . $identifiants,
        'Content-Type: application/x-www-form-urlencoded',
    ], ['grant_type' => 'client_credentials']);

    return $resultat['donnees']['access_token'] ?? null;
}

/**
 * Étape 2 : initie un paiement Orange Money. Retourne une URL de paiement
 * vers laquelle rediriger le client (modèle "Web Payment"), ainsi qu'une
 * référence de transaction à conserver.
 */
function initierPaiementOrange($montant, $numeroTelephone, $referenceExterne) {
    $jeton = obtenirJetonAccesOrange();
    if (!$jeton) {
        return ['succes' => false, 'erreur' => 'Impossible d\'obtenir le jeton d\'accès Orange.'];
    }

    $resultat = requeteHttpOrange('POST', ORANGE_MONEY_URL_BASE . '/webpayment', [
        'Authorization: Bearer ' . $jeton,
        'Content-Type: application/json',
        'Accept: application/json',
    ], [
        'merchant_key' => ORANGE_MONEY_MERCHANT_KEY,
        'currency' => 'XAF',
        'order_id' => $referenceExterne,
        'amount' => $montant,
        'return_url' => URL_CALLBACK_BASE . '/callback_orange.php?statut=succes&ref=' . $referenceExterne,
        'cancel_url' => URL_CALLBACK_BASE . '/callback_orange.php?statut=annule&ref=' . $referenceExterne,
        'notif_url' => URL_CALLBACK_BASE . '/callback_orange.php?ref=' . $referenceExterne,
        'lang' => 'fr',
    ]);

    if (!isset($resultat['donnees']['payment_url'])) {
        return ['succes' => false, 'erreur' => 'Échec de l\'initiation du paiement Orange.', 'detail' => $resultat];
    }

    return [
        'succes' => true,
        'url_paiement' => $resultat['donnees']['payment_url'],
        'reference_id' => $resultat['donnees']['pay_token'] ?? $referenceExterne,
    ];
}
