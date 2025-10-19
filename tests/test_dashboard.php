<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simuler une session utilisateur
$_SESSION['user_id'] = 1;
$_SESSION['is_admin'] = true;
$_SESSION['user_nom'] = 'Test';
$_SESSION['user_prenom'] = 'User';
$_SESSION['user_email'] = 'test@example.com';

require_once __DIR__ . '/vendor/autoload.php';

$dbConfig = require __DIR__ . '/config/database.php';

try {
    $database = new Agora\Services\Database($dbConfig);
    $campaignRepo = new Agora\Repositories\CampaignRepository($database);

    echo "Test des méthodes du repository:\n\n";

    echo "1. Test getStats():\n";
    $stats = $campaignRepo->getStats();
    print_r($stats);

    echo "\n\n2. Test getRecentCampaigns():\n";
    $campaigns = $campaignRepo->getRecentCampaigns(5);
    echo "Nombre de campagnes: " . count($campaigns) . "\n";
    if (!empty($campaigns)) {
        echo "Première campagne:\n";
        echo "  - Titre: " . ($campaigns[0]['titre'] ?? 'N/A') . "\n";
        echo "  - Demandeur: " . ($campaigns[0]['demandeur'] ?? 'N/A') . "\n";
        echo "  - Date événement: " . ($campaigns[0]['date_event_debut'] ?? 'N/A') . "\n";
        echo "  - Date publication: " . ($campaigns[0]['published_at'] ?? 'N/A') . "\n";
        echo "  - Statut: " . ($campaigns[0]['statut'] ?? 'N/A') . "\n";
        echo "  - Priorité: " . ($campaigns[0]['priorite'] ?? 'N/A') . "\n";
        echo "  - Days left: " . ($campaigns[0]['days_left'] ?? 'N/A') . "\n";
        echo "  - Nb supports: " . count($campaigns[0]['supports'] ?? []) . "\n";
        echo "  - Nb validateurs: " . count($campaigns[0]['validateurs'] ?? []) . "\n";
    }

    echo "\n\n3. Test getTimelineCampaigns():\n";
    $timeline = $campaignRepo->getTimelineCampaigns(30);
    echo "Nombre d'éléments timeline: " . count($timeline) . "\n";
    if (!empty($timeline)) {
        print_r($timeline[0]);
    }

    echo "\n\nTous les tests sont OK!";

} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
}
