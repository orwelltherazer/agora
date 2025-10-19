<?php
require 'vendor/autoload.php';
$config = require 'config/database.php';
$db = new Agora\Services\Database($config);

echo "Structure de la table campaigns:\n\n";
$result = $db->fetchAll('DESCRIBE campaigns');
foreach($result as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
