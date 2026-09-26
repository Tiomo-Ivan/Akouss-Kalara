<?php
// =============================================================================
// RÔLE DE CE FICHIER : fonctions d'intégration avec l'API MTN Mobile Money
// (Collections API), suivant le flux standard en 3 étapes :
//   1. Obtenir un jeton d'accès (authentification)
//   2. Demander le paiement (requestToPay)
//   3. Vérifier le statut du paiement
// =============================================================================
require_once __DIR__ . '/../config/paiement_config.php';

/**
 * Effectue une requête HTTP via cURL — utilitaire réutilisé par toutes
 * les fonctions ci-dessous pour dialoguer avec l'API MTN.
 */
function requeteHttpMtn($methode, $url, $entetes = [], $corps = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $methode);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $entetes);
    if ($corps !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corps));
    }
    $reponse = curl_exec($ch);
    $codeHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreurCurl = curl_error($ch);
    curl_close($ch);

    if ($erreurCurl) {
        return ['succes' => false, 'erreur' => $erreurCurl];
    }
    return ['succes' => true, 'code' => $codeHttp, 'donnees' => json_decode($reponse, true)];
}

/** Étape 1 : obtient un jeton d'accès OAuth via Basic Auth (API user + API key). */
function obtenirJetonAccesMtn() {
    $identifiants = base64_encode(MTN_MOMO_API_USER . ':' . MTN_MOMO_API_KEY);
    $resultat = requeteHttpMtn('POST', MTN_MOMO_URL_BASE . '/collection/token/', [
        'Authorization: Basic ' . $identifiants,
        'Ocp-Apim-Subscription-Key: ' . MTN_MOMO_SUBSCRIPTION_KEY,
    ]);

    if (!$resultat['succes'] || $resultat['code'] !== 200) {
        return null;
    }
    return $resultat['donnees']['access_token'] ?? null;
}

/**
 * Étape 2 : demande le paiement (le client recevra une notification sur son
 * téléphone pour confirmer). Retourne l'identifiant de référence à conserver
 * pour vérifier le statut ensuite.
 */
function initierPaiementMtn($montant, $numeroTelephone, $referenceExterne) {
    $jeton = obtenirJetonAccesMtn();
    if (!$jeton) {
        return ['succes' => false, 'erreur' => 'Impossible d\'obtenir le jeton d\'accès MTN.'];
    }

    $referenceId = bin2hex(random_bytes(16)); // identifiant unique de la transaction (UUID-like)
    // Format attendu : UUID v4 — cette génération simplifiée convient pour le sandbox,
    // pour la production préférez une vraie librairie de génération d'UUID v4.

    $resultat = requeteHttpMtn('POST', MTN_MOMO_URL_BASE . '/collection/v1_0/requesttopay', [
        'Authorization: Bearer ' . $jeton,
        'X-Reference-Id: ' . $referenceId,
        'X-Target-Environment: ' . MTN_MOMO_ENVIRONNEMENT_CIBLE,
        'Ocp-Apim-Subscription-Key: ' . MTN_MOMO_SUBSCRIPTION_KEY,
        'Content-Type: application/json',
    ], [
        'amount' => (string) $montant,
        'currency' => 'EUR', // le sandbox MTN utilise EUR ; en production, utiliser XAF
        'externalId' => $referenceExterne,
        'payer' => ['partyIdType' => 'MSISDN', 'partyId' => $numeroTelephone],
        'payerMessage' => 'Paiement Akouss Kalara',
        'payeeNote' => 'Commande Akouss Kalara #' . $referenceExterne,
    ]);

    if (!$resultat['succes'] || $resultat['code'] !== 202) {
        return ['succes' => false, 'erreur' => 'Échec de la demande de paiement MTN.', 'detail' => $resultat];
    }

    return ['succes' => true, 'reference_id' => $referenceId];
}

/** Étape 3 : vérifie le statut d'un paiement précédemment initié. */
function verifierStatutMtn($referenceId) {
    $jeton = obtenirJetonAccesMtn();
    if (!$jeton) return ['statut' => 'ECHEC', 'erreur' => 'Jeton indisponible.'];

    $resultat = requeteHttpMtn('GET', MTN_MOMO_URL_BASE . "/collection/v1_0/requesttopay/$referenceId", [
        'Authorization: Bearer ' . $jeton,
        'X-Target-Environment: ' . MTN_MOMO_ENVIRONNEMENT_CIBLE,
        'Ocp-Apim-Subscription-Key: ' . MTN_MOMO_SUBSCRIPTION_KEY,
    ]);

    if (!$resultat['succes']) return ['statut' => 'ECHEC'];

    // MTN retourne un statut parmi : PENDING, SUCCESSFUL, FAILED
    return ['statut' => $resultat['donnees']['status'] ?? 'PENDING', 'brut' => $resultat['donnees']];
}
