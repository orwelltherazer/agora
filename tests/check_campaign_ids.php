<?php
require 'vendor/autoload.php';

$config = require 'config/database.php';
$db = new Agora\Services\Database($config);

echo "IDs des campagnes créées:\n\n";

$campaigns = $db->fetchAll("SELECT id, titre FROM campaigns ORDER BY id");

if (empty($campaigns)) {
    echo "⚠ Aucune campagne trouvée!\n";
} else {
    foreach ($campaigns as $campaign) {
        echo "ID {$campaign['id']}: {$campaign['titre']}\n";
    }
}
