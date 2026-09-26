<?php
// =============================================================================
// ENDPOINT : GET /api/catalogue/categories.php
// =============================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';
activerCors();

$categories = $pdo->query("SELECT id, nom, slug FROM categories ORDER BY nom")->fetchAll();
repondreJson($categories);
