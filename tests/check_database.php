<?php

require __DIR__ . '/../vendor/autoload.php';

$dbConfig = require __DIR__ . '/../config/database.php';
$db = new Agora\Services\Database($dbConfig);

echo "=== VÉRIFICATION DE LA BASE DE DONNÉES ===\n\n";

// Vérifier les utilisateurs actifs
echo "Utilisateurs actifs:\n";
echo str_repeat("-", 50) . "\n";
$users = $db->fetchAll('SELECT id, nom, prenom, email FROM users WHERE actif = 1 LIMIT 10');
foreach($users as $u) {
    echo "  {$u['id']}: {$u['nom']} {$u['prenom']} ({$u['email']})\n";
}

// Vérifier les supports
echo "\n\nSupports disponibles:\n";
echo str_repeat("-", 50) . "\n";
$supports = $db->fetchAll('SELECT id, nom FROM supports WHERE actif = 1');
foreach($supports as $s) {
    echo "  {$s['id']}: {$s['nom']}\n";
}

// Vérifier les campagnes existantes
echo "\n\nCampagnes existantes:\n";
echo str_repeat("-", 50) . "\n";
$campaigns = $db->fetchAll('SELECT id, titre, statut, created_at FROM campaigns ORDER BY created_at DESC LIMIT 10');
if (empty($campaigns)) {
    echo "  Aucune campagne dans la base\n";
} else {
    foreach($campaigns as $c) {
        echo "  {$c['id']}: {$c['titre']} - Statut: {$c['statut']} - Créée: {$c['created_at']}\n";
    }
}

// Vérifier les tokens de validation
echo "\n\nTokens de validation:\n";
echo str_repeat("-", 50) . "\n";
$tokens = $db->fetchAll('SELECT id, campaign_id, user_id, token, used_at, expires_at FROM validation_tokens LIMIT 10');
if (empty($tokens)) {
    echo "  Aucun token dans la base\n";
} else {
    foreach($tokens as $t) {
        $status = $t['used_at'] ? 'Utilisé' : 'Non utilisé';
        echo "  ID {$t['id']}: Campaign {$t['campaign_id']}, User {$t['user_id']}, {$status}, Expire: {$t['expires_at']}\n";
    }
}

// Vérifier les validations
echo "\n\nValidations:\n";
echo str_repeat("-", 50) . "\n";
$validations = $db->fetchAll('SELECT id, campaign_id, user_id, action, commentaire, created_at FROM validations LIMIT 10');
if (empty($validations)) {
    echo "  Aucune validation dans la base\n";
} else {
    foreach($validations as $v) {
        echo "  ID {$v['id']}: Campaign {$v['campaign_id']}, User {$v['user_id']}, Action: {$v['action']}, Date: {$v['created_at']}\n";
    }
}

echo "\n";
