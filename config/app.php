<?php

/**
 * Configuration de fallback
 *
 * IMPORTANT : Les paramètres de configuration sont maintenant stockés dans la base de données
 * et accessibles via l'interface web /settings (réservée aux administrateurs).
 *
 * Ce fichier sert uniquement de fallback de sécurité si la base de données est inaccessible.
 * Les valeurs ci-dessous sont des valeurs par défaut minimales.
 *
 * Pour modifier la configuration : Accédez à /settings dans l'interface web
 */

return [
    // Configuration de base (fallback uniquement)
    'debug' => true,
    'name' => 'Agora',
    'url' => 'http://localhost/agora/public',
    'timezone' => 'Europe/Paris',

    // Upload - Path nécessaire pour le système de fichiers
    'upload' => [
        'path' => __DIR__ . '/../public/uploads/',
        'max_size' => 10485760, // 10 Mo
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ai', 'psd'],
    ],

    // TOUS LES AUTRES PARAMÈTRES SONT DANS LA BASE DE DONNÉES
    // Accédez à /settings pour les gérer via l'interface web
];
