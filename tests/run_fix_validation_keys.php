<?php

require __DIR__ . '/../vendor/autoload.php';

echo "=== MIGRATION: Correction des clés de validation ===\n\n";

$dbConfig = require __DIR__ . '/../config/database.php';
$db = new Agora\Services\Database($dbConfig);

echo "Renommage des clés...\n";

// Renommer les clés
$migrations = [
    ['old' => 'validation_passerelle_url', 'new' => 'validation_url'],
    ['old' => 'validation_passerelle_api_key', 'new' => 'validation_apikey'],
    ['old' => 'validation_token_expiry_days', 'new' => 'validation_tokendays'],
    ['old' => 'validation_token_days', 'new' => 'validation_tokendays'],
];

foreach ($migrations as $migration) {
    $result = $db->fetch("SELECT id FROM settings WHERE cle = ?", [$migration['old']]);
    if ($result) {
        // Utiliser la méthode query()
        $db->query("UPDATE settings SET cle = :new WHERE cle = :old", [
            'new' => $migration['new'],
            'old' => $migration['old']
        ]);
        echo "  ✓ {$migration['old']} → {$migration['new']}\n";
    } else {
        echo "  - {$migration['old']} n'existe pas (déjà migré ou pas encore créé)\n";
    }
}

echo "\nVérification des nouvelles clés:\n";

$settings = $db->fetchAll("SELECT cle, valeur FROM settings WHERE cle LIKE 'validation%' ORDER BY cle");
foreach ($settings as $setting) {
    $value = $setting['valeur'];
    if (empty($value)) {
        $value = '[VIDE]';
    } elseif (strlen($value) > 50) {
        $value = substr($value, 0, 47) . '...';
    }
    echo "  {$setting['cle']}: $value\n";
}

echo "\nMigration terminée avec succès!\n";
