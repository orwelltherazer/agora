<?php
require 'vendor/autoload.php';
$db = new Agora\Services\Database(require 'config/database.php');

echo "Ajout supports aux campagnes d'octobre...\n\n";

// Récupérer les supports
$supports = $db->fetchAll("SELECT id, nom FROM supports");
$supportMap = [];
foreach ($supports as $s) {
    $supportMap[$s['nom']] = $s['id'];
}

// Récupérer campagnes sans supports
$campaigns = $db->fetchAll("
    SELECT c.id, c.titre
    FROM campaigns c
    LEFT JOIN campaign_supports cs ON c.id = cs.campaign_id
    WHERE cs.campaign_id IS NULL
");

echo "Campagnes sans supports: " . count($campaigns) . "\n\n";

$supportsToAdd = ['Facebook', 'Site web', 'Affichage 4x3', 'Flyers'];
$nb = 0;

foreach ($campaigns as $c) {
    // Ajouter 2-3 supports aléatoires
    shuffle($supportsToAdd);
    $nbSupports = rand(2, 3);

    for ($i = 0; $i < $nbSupports; $i++) {
        if (isset($supportMap[$supportsToAdd[$i]])) {
            $db->insert('campaign_supports', [
                'campaign_id' => $c['id'],
                'support_id' => $supportMap[$supportsToAdd[$i]]
            ]);
            $nb++;
        }
    }
}

echo "✓ $nb supports ajoutés\n";
