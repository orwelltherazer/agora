<?php

use Agora\Services\ConfigService;

/**
 * Fonction helper pour récupérer une valeur de configuration
 * Charge depuis la base de données en priorité, avec fallback sur app.php
 *
 * @param string $key Clé de configuration (notation pointée : 'app.name', 'validation.mode')
 * @param mixed $default Valeur par défaut si la clé n'existe pas
 * @return mixed
 */
function config(string $key, $default = null)
{
    static $appConfig = null;

    // Essayer de charger depuis la base de données
    try {
        $value = ConfigService::getInstance()->get($key);
        if ($value !== null) {
            return $value;
        }
    } catch (Exception $e) {
        // En cas d'erreur (base non accessible, etc.), on continue avec fallback
    }

    // Fallback sur app.php si la base ne contient pas la valeur
    if ($appConfig === null) {
        $appConfig = require __DIR__ . '/../../config/app.php';
    }

    // Naviguer dans le tableau avec la notation pointée
    $parts = explode('.', $key);
    $value = $appConfig;

    foreach ($parts as $part) {
        if (!isset($value[$part])) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

/**
 * Fonction helper pour mettre à jour une valeur de configuration
 *
 * @param string $key Clé de configuration
 * @param mixed $value Nouvelle valeur
 * @return bool
 */
function config_set(string $key, $value): bool
{
    return ConfigService::getInstance()->set($key, $value);
}

/**
 * Charge toute la configuration depuis la base de données
 * avec fallback sur app.php pour les valeurs manquantes
 *
 * @return array
 */
function config_all(): array
{
    static $appConfig = null;

    try {
        $dbConfig = ConfigService::getInstance()->all();

        // Charger app.php pour les valeurs de fallback
        if ($appConfig === null) {
            $appConfig = require __DIR__ . '/../../config/app.php';
        }

        // Merger les deux (la base prime sur le fichier)
        return array_replace_recursive($appConfig, $dbConfig);
    } catch (Exception $e) {
        // En cas d'erreur, retourner uniquement app.php
        if ($appConfig === null) {
            $appConfig = require __DIR__ . '/../../config/app.php';
        }
        return $appConfig;
    }
}
