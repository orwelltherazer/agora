<?php

require __DIR__ . '/../vendor/autoload.php';

echo "=== MIGRATION: Correction des clés email ===\n\n";

$dbConfig = require __DIR__ . '/../config/database.php';
$db = new Agora\Services\Database($dbConfig);

echo "Renommage des clés...\n";

$migrations = [
    ['old' => 'email_smtp_host', 'new' => 'email_host'],
    ['old' => 'email_smtp_port', 'new' => 'email_port'],
    ['old' => 'email_smtp_username', 'new' => 'email_username'],
    ['old' => 'email_smtp_password', 'new' => 'email_password'],
    ['old' => 'email_smtp_encryption', 'new' => 'email_encryption'],
    ['old' => 'email_from_address', 'new' => 'email_fromaddress'],
    ['old' => 'email_from_name', 'new' => 'email_fromname'],
];

foreach ($migrations as $migration) {
    $result = $db->fetch("SELECT id FROM settings WHERE cle = ?", [$migration['old']]);
    if ($result) {
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

$settings = $db->fetchAll("SELECT cle, valeur FROM settings WHERE cle LIKE 'email%' ORDER BY cle");
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
