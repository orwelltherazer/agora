<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Twig\Environment;

class SettingsController
{
    private $db;
    private $twig;

    public function __construct(Database $database, Environment $twig)
    {
        $this->db = $database;
        $this->twig = $twig;
    }

    public function index(): void
    {
        // Récupérer tous les paramètres groupés par catégorie
        $allSettings = $this->db->fetchAll("SELECT * FROM settings ORDER BY categorie, ordre");

        // Organiser par catégorie
        $settingsByCategory = [];
        foreach ($allSettings as $setting) {
            $category = $setting['categorie'] ?? 'general';
            if (!isset($settingsByCategory[$category])) {
                $settingsByCategory[$category] = [];
            }
            $settingsByCategory[$category][] = $setting;
        }

        // Définir les noms conviviaux des catégories
        $categoryLabels = [
            'application' => 'Application',
            'securite' => 'Sécurité',
            'fichiers' => 'Fichiers',
            'pagination' => 'Pagination',
            'notifications' => 'Notifications',
            'validation' => 'Validation',
            'email' => 'Email (SMTP)',
        ];

        echo $this->twig->render('settings/index.twig', [
            'settingsByCategory' => $settingsByCategory,
            'categoryLabels' => $categoryLabels,
        ]);
    }

    public function update(): void
    {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $settingId = str_replace('setting_', '', $key);

                $setting = $this->db->fetch("SELECT * FROM settings WHERE id = :id", ['id' => $settingId]);

                if ($setting) {
                    // Convertir selon le type
                    if ($setting['type'] === 'integer') {
                        $value = (int) $value;
                    } elseif ($setting['type'] === 'boolean') {
                        $value = $value ? '1' : '0';
                    }

                    $this->db->update('settings',
                        ['valeur' => $value],
                        'id = :id',
                        ['id' => $settingId]
                    );
                }
            }
        }

        header('Location: /agora/public/settings');
        exit;
    }
}
