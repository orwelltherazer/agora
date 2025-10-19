<?php

declare(strict_types=1);

// Démarrage de la session
session_start();

// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Chargement des helpers
require_once __DIR__ . '/../src/Helpers/functions.php';

// Chargement des configurations
$dbConfig = require __DIR__ . '/../config/database.php';
$appConfig = require __DIR__ . '/../config/app.php';

// Configuration du timezone (depuis la base ou fallback sur app.php)
$timezone = config('app.timezone', $appConfig['timezone'] ?? 'Europe/Paris');
date_default_timezone_set($timezone);

// Initialisation de la base de données
$database = new Agora\Services\Database($dbConfig);

// Initialisation de Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false, // Activer en production : __DIR__ . '/../cache/twig'
    'debug' => true,
    'auto_reload' => true,
]);

// Initialisation du service mail (si configuré)
// Les paramètres SMTP sont maintenant dans la base de données (config email.*)
$mailService = null;
$smtpUsername = config('email.username', '');
$smtpPassword = config('email.password', '');

if (!empty($smtpUsername) && !empty($smtpPassword)) {
    try {
        // Passer un tableau vide, MailService utilisera config() en interne
        $mailService = new Agora\Services\MailService([], $twig);
    } catch (Exception $e) {
        error_log("Erreur d'initialisation du service mail : " . $e->getMessage());
    }
}

// Ajouter des variables globales Twig
$twig->addGlobal('app', $appConfig);
$twig->addGlobal('session', $_SESSION);

// Fonction helper pour l'URL de base
$twig->addFunction(new \Twig\TwigFunction('asset', function ($path) use ($appConfig) {
    return $appConfig['url'] . '/' . ltrim($path, '/');
}));

$twig->addFunction(new \Twig\TwigFunction('url', function ($path = '') use ($appConfig) {
    return $appConfig['url'] . '/' . ltrim($path, '/');
}));

// Router simple
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($scriptName, '', $requestUri);
$path = trim(parse_url($path, PHP_URL_PATH), '/');

// Séparation path et query string
$pathParts = explode('/', $path);
$controller = $pathParts[0] ?? 'auth';
$action = $pathParts[1] ?? '';

// Routage
try {
    switch ($controller) {
        case '':
            // Redirection vers le dashboard si on est sur la racine
            header('Location: ' . $appConfig['url'] . '/dashboard');
            exit;
            break;

        case 'auth':
            $authController = new Agora\Controllers\AuthController($database, $twig);
            switch ($action) {
                case 'login':
                    $authController->login();
                    break;
                case 'logout':
                    $authController->logout();
                    break;
                default:
                    http_response_code(404);
                    echo $twig->render('errors/404.twig');
            }
            break;

        case 'dashboard':
            $dashboardController = new Agora\Controllers\DashboardController($database, $twig);
            $dashboardController->index();
            break;

        case 'calendar':
            $calendarController = new Agora\Controllers\CalendarController($database, $twig);
            $calendarController->index();
            break;

        case 'campaigns':
            $campaignController = new Agora\Controllers\CampaignController($database, $twig, $mailService);
            switch ($action) {
                case 'index':
                case '':
                    $campaignController->index();
                    break;
                case 'create':
                    $campaignController->create();
                    break;
                case 'store':
                    $campaignController->store();
                    break;
                case 'edit':
                    $id = $pathParts[2] ?? null;
                    $campaignController->edit($id);
                    break;
                case 'update':
                    $id = $pathParts[2] ?? null;
                    $campaignController->update($id);
                    break;
                case 'show':
                    $id = $pathParts[2] ?? null;
                    $campaignController->show($id);
                    break;
                case 'delete-file':
                    $id = $pathParts[2] ?? null;
                    $campaignController->deleteFile($id);
                    break;
                case 'archive':
                    $id = $pathParts[2] ?? null;
                    $campaignController->archive($id);
                    break;
                case 'unarchive':
                    $id = $pathParts[2] ?? null;
                    $campaignController->unarchive($id);
                    break;
                default:
                    http_response_code(404);
                    echo $twig->render('errors/404.twig');
            }
            break;

        case 'users':
            $userController = new Agora\Controllers\UsersController($database, $twig);
            switch ($action) {
                case 'index':
                case '':
                    $userController->index();
                    break;
                case 'create':
                    $userController->create();
                    break;
                case 'store':
                    $userController->store();
                    break;
                case 'edit':
                    $id = $pathParts[2] ?? null;
                    $userController->edit($id);
                    break;
                case 'update':
                    $id = $pathParts[2] ?? null;
                    $userController->update($id);
                    break;
                case 'delete':
                    $id = $pathParts[2] ?? null;
                    $userController->delete($id);
                    break;
                default:
                    http_response_code(404);
                    echo $twig->render('errors/404.twig');
            }
            break;

        case 'settings':
            $settingsController = new Agora\Controllers\SettingsController($database, $twig);
            switch ($action) {
                case 'index':
                case '':
                    $settingsController->index();
                    break;
                case 'update':
                    $settingsController->update();
                    break;
                default:
                    http_response_code(404);
                    echo $twig->render('errors/404.twig');
            }
            break;

        case 'supports':
            $supportsController = new Agora\Controllers\SupportsController($database, $twig);
            switch ($action) {
                case 'index':
                case '':
                    $supportsController->index();
                    break;
                case 'create':
                    $supportsController->create();
                    break;
                case 'store':
                    $supportsController->store();
                    break;
                case 'edit':
                    $id = $pathParts[2] ?? null;
                    $supportsController->edit($id);
                    break;
                case 'update':
                    $id = $pathParts[2] ?? null;
                    $supportsController->update($id);
                    break;
                case 'delete':
                    $id = $pathParts[2] ?? null;
                    $supportsController->delete($id);
                    break;
                default:
                    http_response_code(404);
                    echo $twig->render('errors/404.twig');
            }
            break;

        case 'maintenance':
            $maintenanceController = new Agora\Controllers\MaintenanceController($database, $twig, $mailService);
            switch ($action) {
                case 'index':
                case '':
                    $maintenanceController->index();
                    break;
                case 'test-email':
                    $maintenanceController->testEmail();
                    break;
                case 'test-passerelle':
                    $maintenanceController->testPasserelle();
                    break;
                case 'sync-passerelle':
                    $maintenanceController->syncPasserelle();
                    break;
                case 'logs':
                    $maintenanceController->logs();
                    break;
                default:
                    http_response_code(404);
                    echo $twig->render('errors/404.twig');
            }
            break;

        case 'tests':
            $testsController = new Agora\Controllers\TestsController($database, $twig);
            switch ($action) {
                case 'index':
                case '':
                    $testsController->index();
                    break;
                case 'run-all':
                    $testsController->runAll();
                    break;
                case 'run':
                    $testName = $pathParts[2] ?? null;
                    $testsController->runOne($testName);
                    break;
                default:
                    http_response_code(404);
                    echo $twig->render('errors/404.twig');
            }
            break;

        case 'storage':
            // Route pour servir les fichiers: /storage/uploads/YYYY/MM/filename.jpg
            if ($action === 'uploads' && isset($pathParts[2]) && isset($pathParts[3]) && isset($pathParts[4])) {
                $fileController = new Agora\Controllers\FileController($database);
                $fileController->serve($pathParts[2], $pathParts[3], $pathParts[4]);
            } else {
                http_response_code(404);
                echo "File not found";
            }
            break;

        case 'validate':
            // Route publique de validation avec token
            $publicValidationController = new Agora\Controllers\PublicValidationController($database, $twig);
            $token = $pathParts[1] ?? null;

            if (!$token) {
                http_response_code(404);
                echo $twig->render('errors/404.twig');
                break;
            }

            $subAction = $pathParts[2] ?? '';

            if ($subAction === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $publicValidationController->submit($token);
            } else {
                $publicValidationController->show($token);
            }
            break;

        case 'api':
            // API publique pour la validation
            $apiController = new Agora\Controllers\ApiValidationController($database);

            if ($action === 'validate') {
                $token = $pathParts[2] ?? null;
                if (!$token) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Token manquant']);
                    break;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // POST /api/validate/{token} - Soumettre une validation
                    $apiController->submitValidation($token);
                } else {
                    // GET /api/validate/{token} - Récupérer les données
                    $apiController->getData($token);
                }
            } elseif ($action === 'download') {
                // GET /api/download/{token}/{fileId}
                $token = $pathParts[2] ?? null;
                $fileId = (int)($pathParts[3] ?? 0);

                if (!$token || !$fileId) {
                    http_response_code(404);
                    echo "Fichier non trouvé";
                    break;
                }

                $apiController->downloadFile($token, $fileId);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint non trouvé']);
            }
            break;

        default:
            http_response_code(404);
            echo $twig->render('errors/404.twig');
    }
} catch (Exception $e) {
    // Gestion des erreurs
    if ($appConfig['debug'] ?? false) {
        echo '<pre>';
        echo 'Erreur : ' . $e->getMessage() . "\n";
        echo 'Fichier : ' . $e->getFile() . ':' . $e->getLine() . "\n";
        echo 'Trace : ' . "\n" . $e->getTraceAsString();
        echo '</pre>';
    } else {
        http_response_code(500);
        echo $twig->render('errors/500.twig', ['message' => 'Une erreur est survenue.']);
    }
}
