<?php
/**
 * Script de synchronisation de la passerelle
 * À exécuter manuellement ou via cron pour récupérer les validations depuis la passerelle
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Helpers/functions.php';

// Mode CLI ou Web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // En mode web, afficher un header HTML
    echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>Synchronisation Passerelle</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}pre{background:#fff;padding:15px;border:1px solid #ddd;border-radius:5px;}</style></head><body>";
    echo "<h1>Synchronisation de la Passerelle</h1><pre>";
}

echo "=== SYNCHRONISATION DE LA PASSERELLE ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Charger la configuration
    $dbConfig = require __DIR__ . '/config/database.php';
    $db = new Agora\Services\Database($dbConfig);

    // Vérifier le mode de validation
    $mode = config('validation.mode', 'direct');
    echo "Mode de validation: $mode\n";

    if ($mode !== 'passerelle') {
        echo "\n[INFO] Le mode passerelle n'est pas activé.\n";
        echo "Aucune synchronisation nécessaire en mode '$mode'.\n";
        if (!$isCli) echo "</pre></body></html>";
        exit(0);
    }

    // Vérifier la configuration
    $passerelleUrl = config('validation.url', '');
    $apiKey = config('validation.apikey', '');

    echo "URL de la passerelle: " . ($passerelleUrl ?: '[NON CONFIGURÉ]') . "\n";
    echo "Clé API: " . ($apiKey ? '[CONFIGURÉE]' : '[NON CONFIGURÉE]') . "\n\n";

    if (empty($passerelleUrl) || empty($apiKey)) {
        echo "[ERREUR] Configuration incomplète!\n";
        echo "Veuillez configurer la passerelle dans les paramètres.\n";
        if (!$isCli) echo "</pre></body></html>";
        exit(1);
    }

    // Créer le service de synchronisation
    $syncService = new Agora\Services\PasserelleSyncService($db);

    echo "Démarrage de la synchronisation...\n";
    echo str_repeat("-", 50) . "\n";

    // Lancer la synchronisation
    $result = $syncService->synchronize();

    // Afficher les résultats
    echo "\nRésultats:\n";
    echo str_repeat("-", 50) . "\n";

    if ($result['success']) {
        echo "✓ Synchronisation réussie\n";
        echo "  Validations synchronisées: {$result['synced']}\n";
        echo "  Message: {$result['message']}\n";

        if (!empty($result['errors'])) {
            echo "\n⚠ Avertissements:\n";
            foreach ($result['errors'] as $error) {
                echo "  - $error\n";
            }
        }
    } else {
        echo "✗ Synchronisation échouée\n";
        echo "  Message: {$result['message']}\n";
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Synchronisation terminée\n";

    if (!$isCli) {
        echo "</pre>";
        echo "<p><a href='public/maintenance/test-passerelle'>← Retour au test de la passerelle</a></p>";
        echo "</body></html>";
    }

    exit($result['success'] ? 0 : 1);

} catch (Exception $e) {
    echo "\n[ERREUR FATALE]\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";

    if ($isCli) {
        echo "\nStack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }

    if (!$isCli) {
        echo "</pre></body></html>";
    }

    exit(1);
}
