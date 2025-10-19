<?php
/**
 * Interface web de test pour la passerelle de validation
 * Accessible via: http://localhost/agora/tests/test-passerelle.php
 */

require '../vendor/autoload.php';
require '../src/Helpers/functions.php';

// Configuration de l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction helper pour afficher les résultats
function testResult($success, $message, $details = null) {
    $icon = $success ? '✓' : '✗';
    $class = $success ? 'success' : 'error';
    echo "<div class='test-result $class'>";
    echo "<span class='icon'>$icon</span> ";
    echo "<strong>$message</strong>";
    if ($details) {
        echo "<pre class='details'>" . htmlspecialchars($details) . "</pre>";
    }
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de la Passerelle de Validation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .content {
            padding: 30px;
        }

        .test-section {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .test-section-header {
            background: #f5f5f5;
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }

        .test-section-body {
            padding: 20px;
        }

        .test-result {
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 4px solid;
        }

        .test-result.success {
            background: #e8f5e9;
            border-left-color: #4caf50;
            color: #2e7d32;
        }

        .test-result.error {
            background: #ffebee;
            border-left-color: #f44336;
            color: #c62828;
        }

        .test-result.warning {
            background: #fff3e0;
            border-left-color: #ff9800;
            color: #e65100;
        }

        .test-result.info {
            background: #e3f2fd;
            border-left-color: #2196f3;
            color: #1565c0;
        }

        .icon {
            font-size: 18px;
            font-weight: bold;
            margin-right: 8px;
        }

        .details {
            margin-top: 10px;
            padding: 10px;
            background: rgba(0,0,0,0.05);
            border-radius: 4px;
            font-size: 12px;
            overflow-x: auto;
        }

        .config-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .config-item:last-child {
            border-bottom: none;
        }

        .config-label {
            font-weight: 600;
            color: #666;
            display: inline-block;
            width: 200px;
        }

        .config-value {
            color: #333;
            font-family: monospace;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .badge.ok {
            background: #4caf50;
            color: white;
        }

        .badge.error {
            background: #f44336;
            color: white;
        }

        .badge.warning {
            background: #ff9800;
            color: white;
        }

        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            text-align: center;
        }

        .summary h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .summary-status {
            font-size: 18px;
            font-weight: 600;
            margin-top: 15px;
        }

        .recommendations {
            background: #fff9e6;
            border: 1px solid #ffd54f;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .recommendations h3 {
            color: #f57c00;
            margin-bottom: 15px;
        }

        .recommendations ul {
            list-style: none;
            padding-left: 0;
        }

        .recommendations li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }

        .recommendations li:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #f57c00;
            font-weight: bold;
        }

        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Test de la Passerelle de Validation</h1>
            <p>Diagnostic complet de la configuration et du fonctionnement</p>
        </header>

        <div class="content">
            <?php
            $allTestsPassed = true;
            $warnings = [];
            $recommendations = [];

            // TEST 1: Configuration
            echo '<div class="test-section">';
            echo '<div class="test-section-header">1. Vérification de la Configuration</div>';
            echo '<div class="test-section-body">';

            $mode = config('validation.mode', 'direct');
            $passerelleUrl = config('validation.url', '');
            $apiKey = config('validation.apikey', '');

            echo '<div class="config-item">';
            echo '<span class="config-label">Mode de validation:</span>';
            echo '<span class="config-value">' . ($mode ?: 'NON CONFIGURÉ') . '</span>';
            if ($mode === 'passerelle') {
                echo '<span class="badge ok">ACTIF</span>';
            } else {
                echo '<span class="badge warning">INACTIF</span>';
                $warnings[] = "Le mode passerelle n'est pas activé (mode actuel: $mode)";
                $recommendations[] = "Activez le mode passerelle dans Paramètres > Validation";
            }
            echo '</div>';

            echo '<div class="config-item">';
            echo '<span class="config-label">URL de la passerelle:</span>';
            echo '<span class="config-value">' . ($passerelleUrl ?: 'NON CONFIGURÉ') . '</span>';
            if ($passerelleUrl) {
                echo '<span class="badge ok">OK</span>';
            } else {
                echo '<span class="badge error">MANQUANT</span>';
                $allTestsPassed = false;
            }
            echo '</div>';

            echo '<div class="config-item">';
            echo '<span class="config-label">Clé API:</span>';
            if ($apiKey) {
                echo '<span class="config-value">' . substr($apiKey, 0, 20) . '...</span>';
                echo '<span class="badge ok">OK</span>';
            } else {
                echo '<span class="config-value">NON CONFIGURÉ</span>';
                echo '<span class="badge error">MANQUANT</span>';
                $allTestsPassed = false;
            }
            echo '</div>';

            if (empty($passerelleUrl) || empty($apiKey)) {
                testResult(false, 'Configuration incomplète', 'Veuillez configurer l\'URL de la passerelle et la clé API dans les paramètres.');
                echo '</div></div>';
                goto end_tests;
            } else {
                testResult(true, 'Configuration de base présente');
            }

            echo '</div></div>';

            // TEST 2: Connectivité
            echo '<div class="test-section">';
            echo '<div class="test-section-header">2. Test de Connectivité</div>';
            echo '<div class="test-section-body">';

            $testUrl = rtrim($passerelleUrl, '/') . '/api.php/health';

            $ch = curl_init($testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $endTime = microtime(true);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $responseTime = round(($endTime - $startTime) * 1000, 2);

            echo '<div class="config-item">';
            echo '<span class="config-label">URL testée:</span>';
            echo '<span class="config-value">' . htmlspecialchars($testUrl) . '</span>';
            echo '</div>';

            echo '<div class="config-item">';
            echo '<span class="config-label">Code HTTP:</span>';
            echo '<span class="config-value">' . $httpCode . '</span>';
            echo '</div>';

            echo '<div class="config-item">';
            echo '<span class="config-label">Temps de réponse:</span>';
            echo '<span class="config-value">' . $responseTime . ' ms</span>';
            echo '</div>';

            if ($curlError) {
                testResult(false, 'Erreur de connexion', "Erreur CURL: $curlError\n\nVérifiez que l'URL de la passerelle est correcte et accessible.");
                $allTestsPassed = false;
                echo '</div></div>';
                goto end_tests;
            }

            if ($httpCode !== 200) {
                $errorMsg = "La passerelle n'a pas répondu correctement (code HTTP: $httpCode)";
                if ($httpCode === 401) {
                    $errorMsg .= "\n\nErreur d'authentification. Vérifiez la clé API.";
                } elseif ($httpCode === 404) {
                    $errorMsg .= "\n\nEndpoint non trouvé. Vérifiez que la passerelle est bien déployée.";
                }
                testResult(false, 'Échec de la connexion', $errorMsg);
                $allTestsPassed = false;
                echo '</div></div>';
                goto end_tests;
            }

            $healthData = json_decode($response, true);
            $details = $healthData ? json_encode($healthData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $response;
            testResult(true, 'Connexion établie avec succès', $details);

            echo '</div></div>';

            // TEST 3: Récupération des validations
            echo '<div class="test-section">';
            echo '<div class="test-section-header">3. Test de Récupération des Validations</div>';
            echo '<div class="test-section-body">';

            $pendingUrl = rtrim($passerelleUrl, '/') . '/api.php/pending-validations';

            $ch = curl_init($pendingUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            echo '<div class="config-item">';
            echo '<span class="config-label">URL testée:</span>';
            echo '<span class="config-value">' . htmlspecialchars($pendingUrl) . '</span>';
            echo '</div>';

            if ($curlError || $httpCode !== 200) {
                testResult(false, 'Impossible de récupérer les validations', "Code HTTP: $httpCode\nErreur: " . ($curlError ?: 'Réponse invalide'));
                $allTestsPassed = false;
            } else {
                $data = json_decode($response, true);
                if (!$data || !isset($data['success'])) {
                    testResult(false, 'Réponse invalide de la passerelle', substr($response, 0, 200));
                    $allTestsPassed = false;
                } else {
                    $validationCount = isset($data['data']) ? count($data['data']) : 0;
                    testResult(true, 'Endpoint accessible', "Nombre de validations en attente: $validationCount");

                    if ($validationCount > 0) {
                        $example = json_encode($data['data'][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        echo '<div class="test-result info">';
                        echo '<span class="icon">ℹ</span> ';
                        echo '<strong>Exemple de validation en attente</strong>';
                        echo "<pre class='details'>$example</pre>";
                        echo '</div>';
                    }
                }
            }

            echo '</div></div>';

            // TEST 4: Service de synchronisation
            echo '<div class="test-section">';
            echo '<div class="test-section-header">4. Test du Service de Synchronisation</div>';
            echo '<div class="test-section-body">';

            try {
                $dbConfig = require '../config/database.php';
                $db = new Agora\Services\Database($dbConfig);
                $syncService = new Agora\Services\PasserelleSyncService($db);

                testResult(true, 'Service instancié avec succès');

                // Effectuer une synchronisation
                $result = $syncService->synchronize();

                $resultDetails = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                if ($result['success']) {
                    testResult(true, 'Synchronisation réussie', $resultDetails);

                    if (isset($result['errors']) && !empty($result['errors'])) {
                        $errorsList = implode("\n", $result['errors']);
                        echo '<div class="test-result warning">';
                        echo '<span class="icon">⚠</span> ';
                        echo '<strong>Erreurs lors de la synchronisation</strong>';
                        echo "<pre class='details'>$errorsList</pre>";
                        echo '</div>';
                    }
                } else {
                    testResult(false, 'Synchronisation échouée', $resultDetails);
                    if ($mode !== 'passerelle') {
                        echo '<div class="test-result info">';
                        echo '<span class="icon">ℹ</span> ';
                        echo '<strong>Note:</strong> La synchronisation est désactivée car le mode passerelle n\'est pas actif.';
                        echo '</div>';
                    } else {
                        $allTestsPassed = false;
                    }
                }

            } catch (Exception $e) {
                testResult(false, 'Erreur du service de synchronisation', $e->getMessage() . "\n\n" . $e->getTraceAsString());
                $allTestsPassed = false;
            }

            echo '</div></div>';

            // TEST 5: Base de données
            echo '<div class="test-section">';
            echo '<div class="test-section-header">5. Vérification de la Base de Données</div>';
            echo '<div class="test-section-body">';

            try {
                $tables = ['validation_tokens', 'validations', 'campaigns', 'campaign_validators'];

                foreach ($tables as $table) {
                    $result = $db->fetch("SELECT COUNT(*) as count FROM $table");
                    $count = $result['count'];
                    echo '<div class="config-item">';
                    echo '<span class="config-label">Table <code>' . $table . '</code>:</span>';
                    echo '<span class="config-value">' . $count . ' enregistrement(s)</span>';
                    echo '</div>';
                }

                // Tokens actifs
                $activeTokens = $db->fetch(
                    "SELECT COUNT(*) as count FROM validation_tokens WHERE used_at IS NULL AND expires_at > NOW()"
                );
                echo '<div class="config-item">';
                echo '<span class="config-label">Tokens actifs:</span>';
                echo '<span class="config-value">' . $activeTokens['count'] . '</span>';
                echo '</div>';

                // Tokens expirés
                $expiredTokens = $db->fetch(
                    "SELECT COUNT(*) as count FROM validation_tokens WHERE used_at IS NULL AND expires_at <= NOW()"
                );
                echo '<div class="config-item">';
                echo '<span class="config-label">Tokens expirés:</span>';
                echo '<span class="config-value">' . $expiredTokens['count'] . '</span>';
                if ($expiredTokens['count'] > 0) {
                    echo '<span class="badge warning">À NETTOYER</span>';
                    $recommendations[] = "Nettoyez les tokens expirés de la base de données";
                }
                echo '</div>';

                testResult(true, 'Base de données accessible et opérationnelle');

            } catch (Exception $e) {
                testResult(false, 'Erreur d\'accès à la base de données', $e->getMessage());
                $allTestsPassed = false;
            }

            echo '</div></div>';

            end_tests:

            // Résumé
            echo '<div class="summary">';
            echo '<h2>Résumé du Diagnostic</h2>';

            if ($allTestsPassed && empty($warnings)) {
                echo '<div class="summary-status" style="color: #4caf50;">✓ TOUS LES TESTS SONT PASSÉS</div>';
                echo '<p style="margin-top: 10px;">La passerelle est fonctionnelle et prête à l\'emploi.</p>';
            } elseif ($allTestsPassed && !empty($warnings)) {
                echo '<div class="summary-status" style="color: #ff9800;">⚠ TESTS PASSÉS AVEC AVERTISSEMENTS</div>';
                echo '<p style="margin-top: 10px;">La passerelle fonctionne mais nécessite quelques ajustements.</p>';
            } else {
                echo '<div class="summary-status" style="color: #f44336;">✗ CERTAINS TESTS ONT ÉCHOUÉ</div>';
                echo '<p style="margin-top: 10px;">Des corrections sont nécessaires pour que la passerelle fonctionne correctement.</p>';
            }
            echo '</div>';

            // Recommandations
            if (!empty($recommendations) || !empty($warnings)) {
                echo '<div class="recommendations">';
                echo '<h3>Recommandations</h3>';
                echo '<ul>';

                foreach ($warnings as $warning) {
                    echo '<li>' . htmlspecialchars($warning) . '</li>';
                }

                foreach ($recommendations as $rec) {
                    echo '<li>' . htmlspecialchars($rec) . '</li>';
                }

                if ($mode !== 'passerelle') {
                    echo '<li>Pour activer la passerelle, allez dans <strong>Paramètres > Validation</strong> et définissez le mode sur <code>passerelle</code></li>';
                }

                echo '<li>Pour synchroniser manuellement: créez un fichier <code>sync-passerelle.php</code></li>';
                echo '<li>Pour une synchronisation automatique, configurez une tâche cron toutes les 5 minutes</li>';

                echo '</ul>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>
