<?php

/**
 * API REST pour la synchronisation entre la passerelle et Agora (intranet)
 *
 * Endpoints:
 * - GET /health : Vérification de l'état de l'API
 * - GET /pending-validations : Récupère les validations en attente de synchronisation
 * - POST /sync-completed : Marque des validations comme synchronisées
 * - GET /stats : Statistiques sur les validations (optionnel, pour debug)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database.php';

$config = require __DIR__ . '/config.php';

// Vérification de l'authentification par clé API
function checkApiKey(): bool
{
    global $config;

    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    $expectedAuth = 'Bearer ' . $config['api_key'];

    return $authHeader === $expectedAuth;
}

// Gestion CORS
if (isset($config['cors']['allowed_origins'])) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Vérifier l'authentification
if (!checkApiKey()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Invalid API key'
    ]);
    exit;
}

// Router basique
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/agora/passerelle/api.php', '', $path);
$path = rtrim($path, '/');

try {
    $pdo = getPasserelleDatabase();

    // GET /health - Vérification de l'état de l'API
    if ($method === 'GET' && $path === '/health') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 'ok',
            'message' => 'Passerelle API is running',
            'version' => '1.0',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // GET /pending-validations - Récupère les validations non synchronisées
    if ($method === 'GET' && $path === '/pending-validations') {

        $stmt = $pdo->query("
            SELECT
                id,
                token,
                campaign_id,
                user_id,
                action,
                commentaire,
                validated_at
            FROM validation_responses
            WHERE synced = 0
            ORDER BY validated_at ASC
        ");

        $validations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'count' => count($validations),
            'data' => $validations
        ]);
        exit;
    }

    // POST /sync-completed - Marque des validations comme synchronisées
    if ($method === 'POST' && $path === '/sync-completed') {

        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing or invalid "ids" parameter'
            ]);
            exit;
        }

        // Préparer les placeholders pour la requête
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare("
            UPDATE validation_responses
            SET synced = 1, synced_at = datetime('now')
            WHERE id IN ($placeholders)
        ");

        $stmt->execute($ids);
        $affected = $stmt->rowCount();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'synced_count' => $affected
        ]);
        exit;
    }

    // GET /stats - Statistiques (optionnel, pour debug)
    if ($method === 'GET' && $path === '/stats') {

        $pending = $pdo->query("SELECT COUNT(*) as count FROM validation_responses WHERE synced = 0")->fetch();
        $synced = $pdo->query("SELECT COUNT(*) as count FROM validation_responses WHERE synced = 1")->fetch();
        $total = $pdo->query("SELECT COUNT(*) as count FROM validation_responses")->fetch();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'stats' => [
                'pending' => (int)$pending['count'],
                'synced' => (int)$synced['count'],
                'total' => (int)$total['count']
            ]
        ]);
        exit;
    }

    // Route non trouvée
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Endpoint not found'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
