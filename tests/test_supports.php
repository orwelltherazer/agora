<?php
require 'vendor/autoload.php';
$db = new Agora\Services\Database(require 'config/database.php');

echo "Test supports pour campagne #1:\n\n";

$supports = $db->fetchAll("
    SELECT s.nom
    FROM campaign_supports cs
    JOIN supports s ON cs.support_id = s.id
    WHERE cs.campaign_id = 1
");

echo "Nb supports: " . count($supports) . "\n";
print_r($supports);
