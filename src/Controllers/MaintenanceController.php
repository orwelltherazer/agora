<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Agora\Services\MailService;
use Agora\Services\CampaignLogService;
use Agora\Middleware\Auth;
use Twig\Environment;

class MaintenanceController
{
    private $db;
    private $twig;
    private $mailService;
    private $logService;

    public function __construct(Database $db, Environment $twig, MailService $mailService = null)
    {
        $this->db = $db;
        $this->twig = $twig;
        $this->mailService = $mailService;
        $this->logService = new CampaignLogService($db);
    }

    public function index(): void
    {
        Auth::requireAuth();

        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            http_response_code(403);
            echo "Accès refusé - Réservé aux administrateurs";
            return;
        }

        echo $this->twig->render('maintenance/index.twig');
    }

    public function testEmail(): void
    {
        Auth::requireAuth();

        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            http_response_code(403);
            echo "Accès refusé - Réservé aux administrateurs";
            return;
        }

        $result = [
            'success' => false,
            'message' => '',
        ];

        // Charger les helpers si besoin
        if (!function_exists('config')) {
            require_once __DIR__ . '/../Helpers/functions.php';
        }

        // Vérifier si les paramètres SMTP sont configurés dans la base
        $smtpHost = config('email.host', '');
        $smtpUser = config('email.username', '');
        $smtpPass = config('email.password', '');

        if (empty($smtpHost) || empty($smtpUser) || empty($smtpPass)) {
            $result['message'] = 'Service email non configuré. Accédez à /settings > Email (SMTP) pour configurer les paramètres email.';
        } else {
            // Créer le service mail si nécessaire
            if (!$this->mailService) {
                $this->mailService = new MailService([], $this->twig);
            }
            try {
                // Récupérer l'email de l'utilisateur connecté
                $user = $this->db->fetch("SELECT email, nom, prenom FROM users WHERE id = :id", ['id' => $_SESSION['user_id']]);

                if (!$user || empty($user['email'])) {
                    $result['message'] = 'Email utilisateur non trouvé';
                } else {
                    // Envoyer un email de test
                    $sent = $this->mailService->sendTestEmail($user['email'], $user['prenom'] . ' ' . $user['nom']);

                    if ($sent) {
                        $result['success'] = true;
                        $result['message'] = 'Email de test envoyé avec succès à ' . $user['email'];
                    } else {
                        $result['message'] = 'Échec de l\'envoi de l\'email de test';
                    }
                }
            } catch (\Exception $e) {
                $result['message'] = 'Erreur : ' . $e->getMessage();
            }
        }

        echo $this->twig->render('maintenance/index.twig', [
            'test_result' => $result,
        ]);
    }

    public function testPasserelle(): void
    {
        Auth::requireAuth();

        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            http_response_code(403);
            echo "Accès refusé - Réservé aux administrateurs";
            return;
        }

        // Charger les helpers si besoin
        if (!function_exists('config')) {
            require_once __DIR__ . '/../Helpers/functions.php';
        }

        $testResults = [];
        $allTestsPassed = true;
        $warnings = [];
        $recommendations = [];

        // TEST 1: Configuration
        $mode = config('validation.mode', 'direct');
        $passerelleUrl = config('validation.url', '');
        $apiKey = config('validation.apikey', '');

        $testResults['config'] = [
            'mode' => $mode,
            'url' => $passerelleUrl,
            'apikey' => $apiKey ? substr($apiKey, 0, 20) . '...' : '',
            'passed' => !empty($passerelleUrl) && !empty($apiKey)
        ];

        if ($mode !== 'passerelle') {
            $warnings[] = "Le mode passerelle n'est pas activé (mode actuel: $mode)";
            $recommendations[] = "Activez le mode passerelle dans Paramètres > Validation";
        }

        if (empty($passerelleUrl) || empty($apiKey)) {
            $allTestsPassed = false;
            $testResults['config']['error'] = 'Configuration incomplète';
        }

        // TEST 2: Connectivité (uniquement si config OK)
        if (!empty($passerelleUrl) && !empty($apiKey)) {
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

            $testResults['connectivity'] = [
                'url' => $testUrl,
                'http_code' => $httpCode,
                'response_time' => $responseTime,
                'passed' => $httpCode === 200 && !$curlError,
                'error' => $curlError ?: null,
                'response' => $response ? json_decode($response, true) : null
            ];

            if ($httpCode !== 200 || $curlError) {
                $allTestsPassed = false;
            }

            // TEST 3: Récupération des validations en attente
            if ($httpCode === 200 && !$curlError) {
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

                $data = json_decode($response, true);
                $validationCount = isset($data['data']) ? count($data['data']) : 0;

                $testResults['validations'] = [
                    'url' => $pendingUrl,
                    'http_code' => $httpCode,
                    'passed' => $httpCode === 200 && !$curlError && isset($data['success']),
                    'count' => $validationCount,
                    'error' => $curlError ?: null,
                    'sample' => $validationCount > 0 ? $data['data'][0] : null
                ];

                if ($httpCode !== 200 || $curlError || !isset($data['success'])) {
                    $allTestsPassed = false;
                }
            }

            // TEST 4: Service de synchronisation
            try {
                $syncService = new \Agora\Services\PasserelleSyncService($this->db);
                $result = $syncService->synchronize();

                $testResults['sync'] = [
                    'passed' => $result['success'],
                    'synced' => $result['synced'] ?? 0,
                    'message' => $result['message'] ?? '',
                    'errors' => $result['errors'] ?? []
                ];

                if (!$result['success'] && $mode === 'passerelle') {
                    $allTestsPassed = false;
                }
            } catch (\Exception $e) {
                $testResults['sync'] = [
                    'passed' => false,
                    'error' => $e->getMessage()
                ];
                $allTestsPassed = false;
            }
        }

        // TEST 5: Base de données
        try {
            $tables = ['validation_tokens', 'validations', 'campaigns', 'campaign_validators'];
            $tableCounts = [];

            foreach ($tables as $table) {
                $result = $this->db->fetch("SELECT COUNT(*) as count FROM $table");
                $tableCounts[$table] = $result['count'];
            }

            $activeTokens = $this->db->fetch(
                "SELECT COUNT(*) as count FROM validation_tokens WHERE used_at IS NULL AND expires_at > NOW()"
            );

            $expiredTokens = $this->db->fetch(
                "SELECT COUNT(*) as count FROM validation_tokens WHERE used_at IS NULL AND expires_at <= NOW()"
            );

            $testResults['database'] = [
                'passed' => true,
                'tables' => $tableCounts,
                'active_tokens' => $activeTokens['count'],
                'expired_tokens' => $expiredTokens['count']
            ];

            if ($expiredTokens['count'] > 0) {
                $recommendations[] = "Nettoyez les {$expiredTokens['count']} tokens expirés de la base de données";
            }
        } catch (\Exception $e) {
            $testResults['database'] = [
                'passed' => false,
                'error' => $e->getMessage()
            ];
            $allTestsPassed = false;
        }

        // Afficher la vue
        echo $this->twig->render('maintenance/test_passerelle.twig', [
            'test_results' => $testResults,
            'all_passed' => $allTestsPassed,
            'warnings' => $warnings,
            'recommendations' => $recommendations
        ]);
    }

    public function syncPasserelle(): void
    {
        Auth::requireAuth();

        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Accès refusé']);
            return;
        }

        // Charger les helpers si besoin
        if (!function_exists('config')) {
            require_once __DIR__ . '/../Helpers/functions.php';
        }

        try {
            $syncService = new \Agora\Services\PasserelleSyncService($this->db);
            $result = $syncService->synchronize();

            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'synced' => 0
            ]);
        }
    }

    public function logs(): void
    {
        Auth::requireAuth();

        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            http_response_code(403);
            echo "Accès refusé - Réservé aux administrateurs";
            return;
        }

        // Récupérer les filtres
        $filters = [];
        if (!empty($_GET['campaign_id'])) {
            $filters['campaign_id'] = $_GET['campaign_id'];
        }
        if (!empty($_GET['user_id'])) {
            $filters['user_id'] = $_GET['user_id'];
        }
        if (!empty($_GET['action'])) {
            $filters['action'] = $_GET['action'];
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        // Récupérer les logs
        $logs = $this->logService->getLogs($filters, $perPage, $offset);
        $totalLogs = $this->logService->countLogs($filters);
        $totalPages = ceil($totalLogs / $perPage);

        // Récupérer toutes les campagnes pour le filtre
        $campaigns = $this->db->fetchAll("SELECT id, titre FROM campaigns ORDER BY titre");

        // Récupérer tous les utilisateurs pour le filtre
        $users = $this->db->fetchAll("SELECT id, nom, prenom FROM users ORDER BY nom, prenom");

        echo $this->twig->render('maintenance/logs.twig', [
            'logs' => $logs,
            'filters' => $filters,
            'campaigns' => $campaigns,
            'users' => $users,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_logs' => $totalLogs,
        ]);
    }
}
