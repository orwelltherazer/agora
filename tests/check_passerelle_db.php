<?php

require __DIR__ . '/../passerelle/database.php';

echo "=== VÉRIFICATION BASE DE DONNÉES PASSERELLE ===\n\n";

try {
    $pdo = getPasserelleDatabase();

    // Lister les tables
    echo "Tables disponibles:\n";
    echo str_repeat("-", 50) . "\n";
    $tables = $pdo->query('SELECT name FROM sqlite_master WHERE type="table"')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    // Vérifier les validations en attente
    echo "\n\nValidations enregistrées:\n";
    echo str_repeat("-", 50) . "\n";
    $validations = $pdo->query('SELECT * FROM validation_responses')->fetchAll(PDO::FETCH_ASSOC);

    if (empty($validations)) {
        echo "  Aucune validation dans la passerelle\n";
    } else {
        foreach ($validations as $v) {
            $synced = $v['synced_at'] ? 'Synchronisée' : 'En attente';
            echo "  ID {$v['id']}: Token " . substr($v['token'], 0, 20) . "..., Action: {$v['action']}, {$synced}\n";
        }
    }

    echo "\n";

} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
