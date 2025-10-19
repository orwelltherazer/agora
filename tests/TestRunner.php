<?php

namespace Agora\Tests;

/**
 * Classe de base pour les tests unitaires
 * Permet d'exécuter des tests et de collecter les résultats
 */
abstract class TestRunner
{
    protected $results = [];
    protected $testName = '';

    public function __construct()
    {
        $this->testName = $this->getTestName();
    }

    /**
     * Retourne le nom du test
     */
    abstract protected function getTestName(): string;

    /**
     * Exécute le test
     */
    abstract public function run(): array;

    /**
     * Ajoute un résultat de test
     */
    protected function addResult(string $name, bool $passed, string $message = '', $details = null): void
    {
        $this->results[] = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message,
            'details' => $details,
        ];
    }

    /**
     * Assertion : vérifie qu'une valeur est vraie
     */
    protected function assertTrue($value, string $name, string $failMessage = ''): void
    {
        $passed = (bool)$value;
        $message = $passed ? 'OK' : ($failMessage ?: 'Échec');
        $this->addResult($name, $passed, $message);
    }

    /**
     * Assertion : vérifie qu'une valeur est fausse
     */
    protected function assertFalse($value, string $name, string $failMessage = ''): void
    {
        $passed = !(bool)$value;
        $message = $passed ? 'OK' : ($failMessage ?: 'Échec');
        $this->addResult($name, $passed, $message);
    }

    /**
     * Assertion : vérifie l'égalité
     */
    protected function assertEquals($expected, $actual, string $name, string $failMessage = ''): void
    {
        $passed = $expected === $actual;
        $message = $passed ? 'OK' : ($failMessage ?: "Attendu: $expected, Obtenu: $actual");
        $this->addResult($name, $passed, $message);
    }

    /**
     * Assertion : vérifie la non-égalité
     */
    protected function assertNotEquals($expected, $actual, string $name): void
    {
        $passed = $expected !== $actual;
        $message = $passed ? 'OK' : "Les valeurs ne devraient pas être égales";
        $this->addResult($name, $passed, $message);
    }

    /**
     * Assertion : vérifie qu'une valeur n'est pas nulle
     */
    protected function assertNotNull($value, string $name): void
    {
        $passed = $value !== null;
        $message = $passed ? 'OK' : 'La valeur est nulle';
        $this->addResult($name, $passed, $message);
    }

    /**
     * Assertion : vérifie qu'un tableau n'est pas vide
     */
    protected function assertNotEmpty($value, string $name): void
    {
        $passed = !empty($value);
        $message = $passed ? 'OK' : 'La valeur est vide';
        $this->addResult($name, $passed, $message);
    }

    /**
     * Assertion : vérifie le nombre d'éléments
     */
    protected function assertCount(int $expected, $array, string $name): void
    {
        $actual = is_array($array) ? count($array) : 0;
        $passed = $expected === $actual;
        $message = $passed ? 'OK' : "Attendu: $expected éléments, Obtenu: $actual";
        $this->addResult($name, $passed, $message, $actual);
    }

    /**
     * Retourne les résultats du test
     */
    public function getResults(): array
    {
        $total = count($this->results);
        $passed = count(array_filter($this->results, function($r) { return $r['passed']; }));
        $failed = $total - $passed;

        return [
            'name' => $this->testName,
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
            'tests' => $this->results,
        ];
    }

    /**
     * Retourne un résumé textuel
     */
    public function getSummary(): string
    {
        $results = $this->getResults();
        return sprintf(
            "%s: %d/%d tests passés (%.2f%%)",
            $results['name'],
            $results['passed'],
            $results['total'],
            $results['success_rate']
        );
    }
}
