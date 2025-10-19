<?php
require 'vendor/autoload.php';

$config = require 'config/database.php';
$db = new Agora\Services\Database($config);

echo "Supports disponibles:\n\n";

$supports = $db->fetchAll("SELECT id, nom FROM supports ORDER BY id");

if (empty($supports)) {
    echo "⚠ ATTENTION: Aucun support trouvé dans la base!\n";
    echo "Vous devez d'abord créer des supports.\n";
} else {
    foreach ($supports as $support) {
        echo "  [{$support['id']}] {$support['nom']}\n";
    }
}

echo "\n\nUtilisateurs disponibles:\n\n";

$users = $db->fetchAll("SELECT id, nom, prenom, email FROM users ORDER BY id LIMIT 5");

if (empty($users)) {
    echo "⚠ ATTENTION: Aucun utilisateur trouvé!\n";
} else {
    foreach ($users as $user) {
        echo "  [{$user['id']}] {$user['prenom']} {$user['nom']} ({$user['email']})\n";
    }
}
