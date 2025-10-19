<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Helpers/functions.php';

$dbConfig = require __DIR__ . '/../config/database.php';
$db = new Agora\Services\Database($dbConfig);

echo "=== TEST D'UNE VALIDATION COMPLÈTE ===\n\n";

// Récupérer un token non utilisé
$token = $db->fetch('
    SELECT vt.*, u.nom, u.prenom, c.titre
    FROM validation_tokens vt
    JOIN users u ON vt.user_id = u.id
    JOIN campaigns c ON vt.campaign_id = c.id
    WHERE vt.used_at IS NULL
    AND vt.expires_at > NOW()
    ORDER BY vt.id
    LIMIT 1
');

if (!$token) {
    echo "Aucun token de validation disponible pour le test.\n";
    exit(1);
}

echo "Token sélectionné:\n";
echo "  Campagne: {$token['titre']}\n";
echo "  Validateur: {$token['nom']} {$token['prenom']}\n";
echo "  Token: {$token['token']}\n\n";

// Simuler une validation
$validationMode = config('validation.mode', 'direct');
$baseUrl = ($validationMode === 'passerelle')
    ? config('validation.url')
    : config('app.url');

$validationUrl = $baseUrl . '/validate/' . $token['token'];

echo "URL de validation:\n";
echo "  $validationUrl\n\n";

echo "INSTRUCTIONS POUR LE TEST:\n";
echo str_repeat("=", 70) . "\n";
echo "1. Ouvrez cette URL dans votre navigateur:\n";
echo "   $validationUrl\n\n";
echo "2. Vous devriez voir un formulaire de validation élégant\n";
echo "3. Ajoutez un commentaire (ex: 'Test de validation #1')\n";
echo "4. Cliquez sur 'Valider' (bouton vert)\n";
echo "5. Vous devriez voir un message de succès\n\n";
echo "6. Ensuite, lancez la synchronisation avec:\n";
echo "   php sync-passerelle.php\n\n";
echo "7. Vérifiez le résultat avec:\n";
echo "   php tests/check_database.php\n\n";

echo "\nVoulez-vous simuler automatiquement la validation ? (o/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) === 'o') {
    echo "\n=== SIMULATION DE LA VALIDATION ===\n\n";

    // Simuler la soumission POST vers la passerelle
    require __DIR__ . '/../passerelle/database.php';

    $pdo = getPasserelleDatabase();

    // Vérifier si déjà utilisé
    $stmt = $pdo->prepare("SELECT * FROM validation_responses WHERE token = ?");
    $stmt->execute([$token['token']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo "ERREUR: Ce token a déjà été utilisé!\n";
        exit(1);
    }

    // Insérer la validation
    $stmt = $pdo->prepare("
        INSERT INTO validation_responses (token, campaign_id, user_id, action, commentaire, validated_at)
        VALUES (?, ?, ?, ?, ?, datetime('now'))
    ");

    $action = 'valide';
    $commentaire = 'Test automatique de validation #' . time();

    $stmt->execute([
        $token['token'],
        0, // Sera mis à jour lors de la sync
        0, // Sera mis à jour lors de la sync
        $action,
        $commentaire
    ]);

    echo "✓ Validation enregistrée dans la passerelle\n";
    echo "  Action: $action\n";
    echo "  Commentaire: $commentaire\n\n";

    echo "Maintenant, lancez la synchronisation:\n";
    echo "  php sync-passerelle.php\n";
}
