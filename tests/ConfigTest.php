<?php

namespace Agora\Tests;

require_once __DIR__ . '/TestRunner.php';
require_once __DIR__ . '/../src/Helpers/functions.php';

use Agora\Services\Database;

/**
 * Tests de configuration
 */
class ConfigTest extends TestRunner
{
    private $db;

    public function __construct(Database $db)
    {
        parent::__construct();
        $this->db = $db;
    }

    protected function getTestName(): string
    {
        return "Configuration";
    }

    public function run(): array
    {
        // Test 1: Vérifier que les paramètres existent en base
        $count = $this->db->fetch("SELECT COUNT(*) as count FROM settings");
        $this->assertTrue(
            $count['count'] > 0,
            "Paramètres en base de données",
            "Aucun paramètre trouvé en base"
        );

        // Test 2: Vérifier les catégories
        $categories = $this->db->fetchAll("SELECT DISTINCT categorie FROM settings ORDER BY categorie");
        $expectedCategories = ['application', 'email', 'fichiers', 'notifications', 'pagination', 'securite', 'validation'];
        $this->assertCount(
            count($expectedCategories),
            $categories,
            "Nombre de catégories"
        );

        // Test 3: Tester la fonction config()
        $appName = config('app.name');
        $this->assertNotNull($appName, "Fonction config() - app.name");

        // Test 4: Tester le fallback
        $nonExistent = config('non.existent.key', 'default_value');
        $this->assertEquals(
            'default_value',
            $nonExistent,
            "Fallback sur valeur par défaut"
        );

        // Test 5: Vérifier les paramètres critiques
        $criticalParams = [
            'app.url' => 'URL de l\'application',
            'validation.mode' => 'Mode de validation',
            'upload.max_size' => 'Taille max upload',
        ];

        foreach ($criticalParams as $key => $label) {
            $value = config($key);
            $this->assertNotNull($value, "Paramètre critique: $label");
        }

        // Test 6: Vérifier le type des paramètres
        $uploadSize = config('upload.max_size');
        $this->assertTrue(
            is_numeric($uploadSize),
            "Type integer pour upload.max_size"
        );

        return $this->getResults();
    }
}
