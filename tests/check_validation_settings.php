<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Helpers/functions.php';

echo "=== VÉRIFICATION DES PARAMÈTRES DE VALIDATION ===\n\n";

// Vérifier les settings dans la base
$dbConfig = require __DIR__ . '/../config/database.php';
$db = new Agora\Services\Database($dbConfig);

echo "1. Vérification dans la base de données:\n";
echo str_repeat("-", 50) . "\n";

$settings = $db->fetchAll("SELECT cle, valeur, type FROM settings WHERE cle LIKE 'validation%' ORDER BY cle");

if (empty($settings)) {
    echo "[ERREUR] Aucun paramètre de validation trouvé dans la base de données!\n";
    echo "La migration 'migrate_settings_to_db.sql' n'a probablement pas été exécutée.\n\n";

    echo "Pour corriger, exécutez:\n";
    echo "  mysql -u root agora < database/migrations/migrate_settings_to_db.sql\n\n";
    exit(1);
}

foreach ($settings as $setting) {
    $value = $setting['valeur'];
    if (empty($value)) {
        $value = '[VIDE]';
    } elseif (strlen($value) > 50) {
        $value = substr($value, 0, 47) . '...';
    }

    echo sprintf("  %-30s : %s\n", $setting['cle'], $value);
}

echo "\n2. Vérification via la fonction config():\n";
echo str_repeat("-", 50) . "\n";

$testKeys = [
    'validation.mode',
    'validation.url',
    'validation.apikey',
    'validation.tokendays'
];

foreach ($testKeys as $key) {
    $value = config($key, '[NON TROUVÉ]');
    if (empty($value)) {
        $value = '[VIDE]';
    } elseif (strlen($value) > 50) {
        $value = substr($value, 0, 47) . '...';
    }

    echo sprintf("  %-30s : %s\n", $key, $value);
}

echo "\n3. Diagnostic:\n";
echo str_repeat("-", 50) . "\n";

$mode = config('validation.mode');
$url = config('validation.url');
$apiKey = config('validation.apikey');

if (empty($url) || empty($apiKey)) {
    echo "[ATTENTION] Configuration incomplète!\n\n";

    if (empty($url)) {
        echo "  - validation.url est vide\n";
        echo "    Configurez l'URL dans Paramètres > Validation\n";
    }

    if (empty($apiKey)) {
        echo "  - validation.apikey est vide\n";
        echo "    Configurez la clé API dans Paramètres > Validation\n";
    }

    echo "\nOu mettez à jour directement dans la base:\n";
    if (empty($url)) {
        echo "  UPDATE settings SET valeur = 'https://votre-passerelle.com' WHERE cle = 'validation_url';\n";
    }
    if (empty($apiKey)) {
        echo "  UPDATE settings SET valeur = 'VOTRE_CLE_API' WHERE cle = 'validation_apikey';\n";
    }
} else {
    echo "[OK] Configuration complète\n";
    echo "  Mode: $mode\n";
    echo "  URL: $url\n";
    echo "  API Key: " . substr($apiKey, 0, 20) . "...\n";
}

echo "\n";
