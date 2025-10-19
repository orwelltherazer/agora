<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Agora\Middleware\Auth;
use Twig\Environment;

class TestsController
{
    private $db;
    private $twig;

    public function __construct(Database $db, Environment $twig)
    {
        $this->db = $db;
        $this->twig = $twig;

        // Charger la classe de base TestRunner
        require_once __DIR__ . '/../../tests/TestRunner.php';

        // Charger les helpers si nécessaire
        if (!function_exists('config')) {
            require_once __DIR__ . '/../Helpers/functions.php';
        }
    }

    /**
     * Liste des tests disponibles
     */
    public function index(): void
    {
        Auth::requireAuth();
        Auth::requireAdmin();

        echo $this->twig->render('tests/index.twig', [
            'tests' => $this->getAvailableTests()
        ]);
    }

    /**
     * Exécute tous les tests
     */
    public function runAll(): void
    {
        Auth::requireAuth();
        Auth::requireAdmin();

        $results = [];
        $testClasses = $this->getAvailableTests();

        foreach ($testClasses as $testInfo) {
            // Charger le fichier de test
            if (isset($testInfo['file']) && file_exists($testInfo['file'])) {
                require_once $testInfo['file'];
            }

            $className = $testInfo['class'];
            if (class_exists($className)) {
                $test = new $className($this->db);
                $results[] = $test->run();
            }
        }

        echo $this->twig->render('tests/results.twig', [
            'results' => $results,
            'totalTests' => array_sum(array_column($results, 'total')),
            'totalPassed' => array_sum(array_column($results, 'passed')),
            'totalFailed' => array_sum(array_column($results, 'failed')),
        ]);
    }

    /**
     * Exécute un test spécifique
     */
    public function runOne(?string $testName): void
    {
        Auth::requireAuth();
        Auth::requireAdmin();

        if (!$testName) {
            http_response_code(400);
            echo "Test non spécifié";
            return;
        }

        $testClasses = $this->getAvailableTests();
        $testClass = null;
        $testFile = null;

        foreach ($testClasses as $info) {
            if ($info['name'] === $testName) {
                $testClass = $info['class'];
                $testFile = $info['file'] ?? null;
                break;
            }
        }

        if (!$testClass) {
            http_response_code(404);
            echo "Test introuvable: $testName";
            return;
        }

        // Charger le fichier de test
        if ($testFile && file_exists($testFile)) {
            require_once $testFile;
        }

        if (!class_exists($testClass)) {
            http_response_code(500);
            echo "Erreur: Classe de test introuvable ($testClass)";
            return;
        }

        $test = new $testClass($this->db);
        $result = $test->run();

        echo $this->twig->render('tests/results.twig', [
            'results' => [$result],
            'totalTests' => $result['total'],
            'totalPassed' => $result['passed'],
            'totalFailed' => $result['failed'],
            'singleTest' => true,
        ]);
    }

    /**
     * Retourne la liste des tests disponibles
     */
    private function getAvailableTests(): array
    {
        return [
            [
                'name' => 'database',
                'label' => 'Base de données',
                'description' => 'Vérifie la connexion et l\'intégrité de la base de données',
                'icon' => 'fa-database',
                'class' => 'Agora\\Tests\\DatabaseTest',
                'file' => __DIR__ . '/../../tests/DatabaseTest.php',
            ],
            [
                'name' => 'config',
                'label' => 'Configuration',
                'description' => 'Teste le système de configuration et les paramètres',
                'icon' => 'fa-cog',
                'class' => 'Agora\\Tests\\ConfigTest',
                'file' => __DIR__ . '/../../tests/ConfigTest.php',
            ],
        ];
    }
}
