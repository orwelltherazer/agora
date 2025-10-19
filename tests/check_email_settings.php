<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Helpers/functions.php';

echo "=== VÉRIFICATION DES PARAMÈTRES EMAIL ===\n\n";

$dbConfig = require __DIR__ . '/../config/database.php';
$db = new Agora\Services\Database($dbConfig);

echo "1. Paramètres dans la base de données:\n";
echo str_repeat("-", 50) . "\n";

$settings = $db->fetchAll("SELECT cle, valeur FROM settings WHERE cle LIKE 'email%' ORDER BY cle");

if (empty($settings)) {
    echo "[ERREUR] Aucun paramètre email trouvé!\n";
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

echo "\n2. Récupération via config():\n";
echo str_repeat("-", 50) . "\n";

$testKeys = [
    'email.host',
    'email.username',
    'email.password',
    'email.port',
    'email.encryption',
    'email.fromaddress',
    'email.fromname'
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

$host = config('email.host');
$username = config('email.username');
$password = config('email.password');

if (empty($host) || empty($username) || empty($password)) {
    echo "[ATTENTION] Configuration email incomplète!\n\n";

    if (empty($host)) {
        echo "  - email.host est vide\n";
    }
    if (empty($username)) {
        echo "  - email.username est vide\n";
    }
    if (empty($password)) {
        echo "  - email.password est vide\n";
    }
} else {
    echo "[OK] Configuration email complète\n";
    echo "  Host: $host\n";
    echo "  User: $username\n";
    echo "  Pass: " . (empty($password) ? '[VIDE]' : '[CONFIGURÉ]') . "\n";
}

echo "\n";
