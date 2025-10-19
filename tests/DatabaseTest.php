<?php

namespace Agora\Tests;

require_once __DIR__ . '/TestRunner.php';

use Agora\Services\Database;

/**
 * Tests de la base de données
 */
class DatabaseTest extends TestRunner
{
    private $db;

    public function __construct(Database $db)
    {
        parent::__construct();
        $this->db = $db;
    }

    protected function getTestName(): string
    {
        return "Base de données";
    }

    public function run(): array
    {
        // Test 1: Connexion
        try {
            $this->db->getConnection();
            $this->assertTrue(true, "Connexion à la base de données");
        } catch (\Exception $e) {
            $this->assertTrue(false, "Connexion à la base de données", $e->getMessage());
        }

        // Test 2: Tables principales
        $requiredTables = [
            'users',
            'roles',
            'user_roles',
            'campaigns',
            'campaign_supports',
            'campaign_validators',
            'supports',
            'settings',
            'validations',
            'validation_tokens',
            'campaign_logs',
            'files',
        ];

        foreach ($requiredTables as $table) {
            try {
                $result = $this->db->fetch("SHOW TABLES LIKE '$table'");
                $this->assertNotNull($result, "Table '$table' existe");
            } catch (\Exception $e) {
                $this->assertTrue(false, "Table '$table' existe", $e->getMessage());
            }
        }

        // Test 3: Vérifier les données minimales
        $checks = [
            ['table' => 'roles', 'label' => 'Rôles définis'],
            ['table' => 'supports', 'label' => 'Supports définis'],
            ['table' => 'settings', 'label' => 'Paramètres définis'],
        ];

        foreach ($checks as $check) {
            $count = $this->db->fetch("SELECT COUNT(*) as count FROM {$check['table']}");
            $this->assertTrue(
                $count['count'] > 0,
                $check['label'],
                "Aucune donnée dans {$check['table']}"
            );
        }

        // Test 4: Intégrité référentielle (foreign keys)
        try {
            $fkCheck = $this->db->fetch("SELECT @@FOREIGN_KEY_CHECKS as fk");
            $this->assertEquals(
                1,
                (int)$fkCheck['fk'],
                "Foreign key checks activés"
            );
        } catch (\Exception $e) {
            $this->assertTrue(false, "Foreign key checks", $e->getMessage());
        }

        return $this->getResults();
    }
}
