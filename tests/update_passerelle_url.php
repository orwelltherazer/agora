<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Helpers/functions.php';

echo "=== MISE À JOUR DE L'URL DE LA PASSERELLE ===\n\n";

// URL actuelle
$currentUrl = config('validation.url', '');
echo "URL actuelle: $currentUrl\n";

// Nouvelle URL (locale pour le développement)
$newUrl = 'http://localhost/agora/passerelle';
echo "Nouvelle URL: $newUrl\n\n";

// Mettre à jour
$result = config_set('validation.url', $newUrl);

if ($result) {
    echo "✓ URL mise à jour avec succès!\n";

    // Vérifier
    $verif = config('validation.url');
    echo "Vérification: $verif\n";
} else {
    echo "✗ Erreur lors de la mise à jour\n";
}

echo "\n";
