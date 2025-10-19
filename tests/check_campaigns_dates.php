<?php
require 'vendor/autoload.php';

$config = require 'config/database.php';
$db = new Agora\Services\Database($config);

echo "Vérification des dates des campagnes:\n\n";

$campaigns = $db->fetchAll("SELECT id, titre, date_event_debut, date_event_fin, date_campagne_debut, date_campagne_fin FROM campaigns LIMIT 10");

if (empty($campaigns)) {
    echo "Aucune campagne trouvée.\n";
} else {
    foreach ($campaigns as $campaign) {
        echo "Campagne #{$campaign['id']}: {$campaign['titre']}\n";
        echo "  - Événement: {$campaign['date_event_debut']}";
        if ($campaign['date_event_fin']) {
            echo " → {$campaign['date_event_fin']}";
        }
        echo "\n";
        echo "  - Parution: " . ($campaign['date_campagne_debut'] ?: 'NON RENSEIGNÉE');
        if ($campaign['date_campagne_fin']) {
            echo " → {$campaign['date_campagne_fin']}";
        }
        echo "\n\n";
    }
}
