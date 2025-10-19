<?php

require __DIR__ . '/vendor/autoload.php';

$dbConfig = require __DIR__ . '/config/database.php';
$db = new Agora\Services\Database($dbConfig);

$users = $db->fetchAll('SELECT u.id, u.email, u.nom, u.prenom, GROUP_CONCAT(r.nom) as roles
                        FROM users u
                        LEFT JOIN user_roles ur ON u.id = ur.user_id
                        LEFT JOIN roles r ON ur.role_id = r.id
                        GROUP BY u.id');

foreach($users as $user) {
    echo $user['email'] . ' - Rôles: ' . ($user['roles'] ?? 'aucun') . PHP_EOL;
}
