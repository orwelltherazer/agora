<?php

/**
 * Initialise et retourne la connexion SQLite pour la passerelle
 */
function getPasserelleDatabase(): PDO
{
    $config = require __DIR__ . '/config.php';
    $dbPath = $config['database']['path'];

    // Créer le répertoire data s'il n'existe pas
    $dataDir = dirname($dbPath);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    // Connexion SQLite
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Créer les tables si elles n'existent pas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS validation_responses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            token TEXT UNIQUE NOT NULL,
            campaign_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            action TEXT NOT NULL CHECK(action IN ('valide', 'refuse')),
            commentaire TEXT,
            validated_at TEXT NOT NULL,
            synced INTEGER DEFAULT 0,
            synced_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE INDEX IF NOT EXISTS idx_token ON validation_responses(token);
        CREATE INDEX IF NOT EXISTS idx_synced ON validation_responses(synced);
        CREATE INDEX IF NOT EXISTS idx_validated_at ON validation_responses(validated_at);
    ");

    return $pdo;
}
