<?php

return [
    // Base de données SQLite pour stocker temporairement les validations
    'database' => [
        'path' => __DIR__ . '/data/validation_queue.db',
    ],

    // Clé API pour sécuriser les échanges avec Agora (doit être identique à celle dans config/app.php)
    'api_key' => 'CHANGE_ME_IN_PRODUCTION_SECRET_KEY_12345',

    // Configuration CORS si nécessaire
    'cors' => [
        'allowed_origins' => ['*'], // Restreindre en production
    ],

    // URL de base de l'application Agora (pour les redirections après validation)
    'agora_url' => 'http://localhost/agora/public',
];
