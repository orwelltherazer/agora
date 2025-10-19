<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Helpers/functions.php';

$dbConfig = require __DIR__ . '/../config/database.php';
$db = new Agora\Services\Database($dbConfig);

echo "=== URLS DE VALIDATION POUR LE TEST ===\n\n";

// Récupérer les tokens non utilisés
$tokens = $db->fetchAll('
    SELECT vt.*, u.nom, u.prenom, u.email, c.titre as campaign_title
    FROM validation_tokens vt
    JOIN users u ON vt.user_id = u.id
    JOIN campaigns c ON vt.campaign_id = c.id
    WHERE vt.used_at IS NULL
    AND vt.expires_at > NOW()
    ORDER BY vt.created_at DESC
');

if (empty($tokens)) {
    echo "Aucun token de validation disponible.\n";
    exit;
}

// Récupérer l'URL de base selon le mode
$validationMode = config('validation.mode', 'direct');
$baseUrl = ($validationMode === 'passerelle')
    ? config('validation.url')
    : config('app.url');

echo "Mode de validation: $validationMode\n";
echo "URL de base: $baseUrl\n";
echo str_repeat("=", 70) . "\n\n";

foreach ($tokens as $token) {
    $validationUrl = $baseUrl . '/validate/' . $token['token'];

    echo "Campagne: {$token['campaign_title']}\n";
    echo "Validateur: {$token['nom']} {$token['prenom']} ({$token['email']})\n";
    echo "Token: {$token['token']}\n";
    echo "Expire le: {$token['expires_at']}\n";
    echo "URL de validation:\n";
    echo "  $validationUrl\n";
    echo str_repeat("-", 70) . "\n\n";
}

echo "\nPour tester:\n";
echo "1. Copiez une des URLs ci-dessus\n";
echo "2. Ouvrez-la dans un navigateur\n";
echo "3. Remplissez le formulaire de validation\n";
echo "4. Ensuite, lancez la synchronisation depuis Maintenance > Test de la Passerelle\n\n";
