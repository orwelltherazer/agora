<?php

namespace Agora\Services;

use Agora\Services\Database;

/**
 * Service de gestion de la configuration
 * Lit les paramètres depuis la base de données au lieu du fichier app.php
 */
class ConfigService
{
    private static $instance = null;
    private $db;
    private $config = [];
    private $loaded = false;

    private function __construct()
    {
        // Charger la config de la base de données
        $dbConfig = require __DIR__ . '/../../config/database.php';
        $this->db = new Database($dbConfig);
    }

    /**
     * Singleton pour éviter de recharger les paramètres à chaque fois
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Charge tous les paramètres depuis la base de données
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $settings = $this->db->fetchAll("SELECT cle, valeur, type FROM settings");

        foreach ($settings as $setting) {
            $value = $setting['valeur'];

            // Convertir selon le type
            switch ($setting['type']) {
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'boolean':
                    $value = $value === '1' || $value === 'true';
                    break;
                case 'json':
                    $value = json_decode($value, true) ?? [];
                    break;
                // string et password restent en string
            }

            // Construire le tableau de config hiérarchique
            $this->setNestedValue($setting['cle'], $value);
        }

        $this->loaded = true;
    }

    /**
     * Construit un tableau hiérarchique depuis une clé avec underscores
     * Ex: "app_name" devient ['app' => ['name' => 'Agora']]
     *     "validation_mode" devient ['validation' => ['mode' => 'passerelle']]
     */
    private function setNestedValue(string $key, $value): void
    {
        $parts = explode('_', $key);

        if (count($parts) === 1) {
            // Pas de hiérarchie
            $this->config[$key] = $value;
            return;
        }

        // Créer la structure hiérarchique
        $current = &$this->config;
        $lastPart = array_pop($parts);

        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }

        $current[$lastPart] = $value;
    }

    /**
     * Récupère un paramètre de configuration
     * Supporte la notation pointée : get('validation.mode')
     */
    public function get(string $key, $default = null)
    {
        $this->load();

        $parts = explode('.', $key);
        $value = $this->config;

        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Récupère toute la configuration
     */
    public function all(): array
    {
        $this->load();
        return $this->config;
    }

    /**
     * Met à jour un paramètre dans la base de données
     */
    public function set(string $key, $value): bool
    {
        // Convertir la clé pointée en underscore
        $dbKey = str_replace('.', '_', $key);

        // Récupérer le setting existant
        $setting = $this->db->fetch("SELECT id, type FROM settings WHERE cle = :cle", ['cle' => $dbKey]);

        if (!$setting) {
            return false;
        }

        // Convertir la valeur selon le type
        $dbValue = $value;
        switch ($setting['type']) {
            case 'boolean':
                $dbValue = $value ? '1' : '0';
                break;
            case 'integer':
                $dbValue = (string)(int)$value;
                break;
            case 'json':
                $dbValue = json_encode($value);
                break;
            default:
                $dbValue = (string)$value;
        }

        // Mettre à jour en base
        $this->db->update('settings',
            ['valeur' => $dbValue],
            'id = :id',
            ['id' => $setting['id']]
        );

        // Recharger la config
        $this->loaded = false;
        $this->config = [];
        $this->load();

        return true;
    }

    /**
     * Efface le cache de configuration (force le rechargement)
     */
    public function refresh(): void
    {
        $this->loaded = false;
        $this->config = [];
    }
}
