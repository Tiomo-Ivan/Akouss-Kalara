<?php

// =============================================================================
// Configuration des fichiers numériques Akouss Kalara
// =============================================================================

define(
    'STOCKAGE_LIVRES_NUMERIQUES',
    dirname(__DIR__, 2) . '/stockage/livres_numeriques'
);

// Taille maximale d'un livre numérique : 20 Mo
define('TAILLE_MAX_LIVRE_NUMERIQUE', 20 * 1024 * 1024);

// Formats autorisés pour les livres numériques
define('EXTENSIONS_LIVRE_NUMERIQUE', ['pdf']);
define('MIMES_LIVRE_NUMERIQUE', ['application/pdf']);
