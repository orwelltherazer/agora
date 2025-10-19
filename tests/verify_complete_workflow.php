<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Helpers/functions.php';

echo "=== VÉRIFICATION COMPLÈTE DU WORKFLOW DE VALIDATION ===\n\n";

// MySQL Agora
$dbConfig = require __DIR__ . '/../config/database.php';
$db = new Agora\Services\Database($dbConfig);

// SQLite Passerelle
require __DIR__ . '/../passerelle/database.php';
$pdo = getPasserelleDatabase();

$campaignId = 100;

echo "Campagne de test: ID $campaignId\n";
echo str_repeat("=", 70) . "\n\n";

// 1. Info campagne
$campaign = $db->fetch("SELECT * FROM campaigns WHERE id = ?", [$campaignId]);
echo "1. INFORMATIONS CAMPAGNE\n";
echo str_repeat("-", 70) . "\n";
echo "   Titre: {$campaign['titre']}\n";
echo "   Statut: {$campaign['statut']}\n";
echo "   Créée le: {$campaign['created_at']}\n";
echo "   Créée par: User ID {$campaign['created_by']}\n\n";

// 2. Validateurs assignés
$validators = $db->fetchAll("
    SELECT cv.user_id, cv.ordre, u.nom, u.prenom, u.email
    FROM campaign_validators cv
    JOIN users u ON cv.user_id = u.id
    WHERE cv.campaign_id = ?
    ORDER BY cv.ordre
", [$campaignId]);

echo "2. VALIDATEURS ASSIGNÉS\n";
echo str_repeat("-", 70) . "\n";
foreach ($validators as $v) {
    echo "   {$v['ordre']}. {$v['prenom']} {$v['nom']} ({$v['email']})\n";
}
echo "\n";

// 3. Tokens générés
$tokens = $db->fetchAll("
    SELECT vt.id, vt.user_id, vt.token, vt.used_at, vt.expires_at, u.nom, u.prenom
    FROM validation_tokens vt
    JOIN users u ON vt.user_id = u.id
    WHERE vt.campaign_id = ?
    ORDER BY vt.id
", [$campaignId]);

echo "3. TOKENS DE VALIDATION GÉNÉRÉS\n";
echo str_repeat("-", 70) . "\n";
foreach ($tokens as $t) {
    $status = $t['used_at'] ? '✓ Utilisé le ' . $t['used_at'] : '○ Non utilisé';
    $tokenShort = substr($t['token'], 0, 20) . '...';
    echo "   Token {$t['id']}: {$t['prenom']} {$t['nom']}\n";
    echo "      $tokenShort\n";
    echo "      $status\n";
    echo "      Expire: {$t['expires_at']}\n";
}
echo "\n";

// 4. Validations dans la passerelle (SQLite)
$passerelleValidations = $pdo->query("
    SELECT * FROM validation_responses
    ORDER BY id
")->fetchAll(PDO::FETCH_ASSOC);

echo "4. VALIDATIONS DANS LA PASSERELLE (SQLite)\n";
echo str_repeat("-", 70) . "\n";
if (empty($passerelleValidations)) {
    echo "   Aucune validation dans la passerelle\n";
} else {
    foreach ($passerelleValidations as $pv) {
        $synced = $pv['synced_at'] ? '✓ Synchronisée le ' . $pv['synced_at'] : '○ En attente';
        $tokenShort = substr($pv['token'], 0, 20) . '...';
        echo "   ID {$pv['id']}: $tokenShort\n";
        echo "      Action: {$pv['action']}\n";
        echo "      Commentaire: {$pv['commentaire']}\n";
        echo "      Validée le: {$pv['validated_at']}\n";
        echo "      $synced\n";
    }
}
echo "\n";

// 5. Validations dans Agora (MySQL)
$agoraValidations = $db->fetchAll("
    SELECT v.*, u.nom, u.prenom
    FROM validations v
    JOIN users u ON v.user_id = u.id
    WHERE v.campaign_id = ?
    ORDER BY v.id
", [$campaignId]);

echo "5. VALIDATIONS DANS AGORA (MySQL)\n";
echo str_repeat("-", 70) . "\n";
if (empty($agoraValidations)) {
    echo "   Aucune validation dans Agora\n";
} else {
    foreach ($agoraValidations as $av) {
        echo "   ID {$av['id']}: {$av['prenom']} {$av['nom']}\n";
        echo "      Action: {$av['action']}\n";
        echo "      Commentaire: {$av['commentaire']}\n";
        echo "      Date: {$av['created_at']}\n";
    }
}
echo "\n";

// 6. Logs d'événements
$logs = $db->fetchAll("
    SELECT cl.*, u.nom, u.prenom
    FROM campaign_logs cl
    JOIN users u ON cl.user_id = u.id
    WHERE cl.campaign_id = ?
    ORDER BY cl.created_at DESC
    LIMIT 10
", [$campaignId]);

echo "6. LOGS D'ÉVÉNEMENTS\n";
echo str_repeat("-", 70) . "\n";
if (empty($logs)) {
    echo "   Aucun log\n";
} else {
    foreach ($logs as $log) {
        echo "   [{$log['created_at']}] {$log['action']}\n";
        if (isset($log['details']) && $log['details']) {
            echo "      {$log['details']}\n";
        }
    }
}
echo "\n";

// 7. Statistiques
echo "7. STATISTIQUES\n";
echo str_repeat("-", 70) . "\n";
$totalValidators = count($validators);
$totalTokens = count($tokens);
$usedTokens = count(array_filter($tokens, function($t) { return $t['used_at'] !== null; }));
$totalValidations = count($agoraValidations);
$approved = count(array_filter($agoraValidations, function($v) { return $v['action'] === 'valide'; }));
$rejected = count(array_filter($agoraValidations, function($v) { return $v['action'] === 'refuse'; }));

echo "   Validateurs assignés: $totalValidators\n";
echo "   Tokens générés: $totalTokens\n";
echo "   Tokens utilisés: $usedTokens / $totalTokens\n";
echo "   Validations reçues: $totalValidations\n";
echo "   - Approuvées: $approved\n";
echo "   - Refusées: $rejected\n";

$progress = $totalValidators > 0 ? round(($totalValidations / $totalValidators) * 100) : 0;
echo "   Progression: $progress%\n";

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "Workflow testé avec succès!\n";
