<?php
// =============================================================================
// RÔLE DE CE FICHIER : identifiants des API de paiement MTN Mobile Money et
// Orange Money, obtenus via ARITED (sandbox).
//
// ⚠️ IMPORTANT — À COMPLÉTER OBLIGATOIREMENT AVANT DE TESTER LE PAIEMENT :
// Remplacez CHAQUE valeur ci-dessous par vos vraies clés fournies par
// ARITED. Sans cela, aucun appel de paiement ne pourra fonctionner.
// Les noms exacts des paramètres (clé d'abonnement, utilisateur API...)
// peuvent varier légèrement selon la documentation qu'ARITED vous a
// transmise : ajustez les noms de variables si besoin, la LOGIQUE reste
// la même (flux standard MTN MoMo Collections API / Orange Money Web
// Payment API), mais les noms de champs exacts doivent correspondre à
// LEUR documentation, pas uniquement à ce fichier générique.
// =============================================================================

// --- MTN Mobile Money (Collections API) ---
define('MTN_MOMO_URL_BASE', 'https://sandbox.momodeveloper.mtn.com');      // URL sandbox MTN — à confirmer avec la doc ARITED
define('MTN_MOMO_SUBSCRIPTION_KEY', 'VOTRE_CLE_ABONNEMENT_MTN');           // Ocp-Apim-Subscription-Key
define('MTN_MOMO_API_USER', 'VOTRE_API_USER_MTN');                          // identifiant API utilisateur (UUID)
define('MTN_MOMO_API_KEY', 'VOTRE_API_KEY_MTN');                            // clé API utilisateur
define('MTN_MOMO_ENVIRONNEMENT_CIBLE', 'sandbox');                          // "sandbox" ou "mtncameroon" en production

// --- Orange Money (Web Payment API) ---
define('ORANGE_MONEY_URL_BASE', 'https://api.orange.com/orange-money-webpay/cm/v1'); // URL Cameroun — à confirmer avec la doc ARITED
define('ORANGE_MONEY_MERCHANT_KEY', 'VOTRE_CLE_MARCHAND_ORANGE');
define('ORANGE_MONEY_CLIENT_ID', 'VOTRE_CLIENT_ID_ORANGE');
define('ORANGE_MONEY_CLIENT_SECRET', 'VOTRE_CLIENT_SECRET_ORANGE');

// URL vers laquelle MTN/Orange enverront leur notification de paiement
// (webhook/callback). Doit être accessible publiquement en production
// (pas "localhost") — pour les tests locaux, un outil comme ngrok permet
// d'exposer temporairement votre serveur XAMPP à internet.
define('URL_CALLBACK_BASE', 'http://localhost/akouss-kalara-php/api/paiement');
