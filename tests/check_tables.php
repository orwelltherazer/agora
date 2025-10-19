<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dbConfig = require __DIR__ . '/../config/database.php';
$database = new Agora\Services\Database($dbConfig);
$pdo = $database->getConnection();

echo "Structure de la table campaigns:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$stmt = $pdo->query('DESCRIBE campaigns');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-20s %-20s %s\n", $row['Field'], $row['Type'], $row['Key']);
}

echo "\nStructure de la table users:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$stmt = $pdo->query('DESCRIBE users');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-20s %-20s %s\n", $row['Field'], $row['Type'], $row['Key']);
}
