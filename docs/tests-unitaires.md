# Tests Unitaires

## Vue d'ensemble

Agora dispose d'un système de tests unitaires intégré accessible via l'interface web d'administration. Les tests permettent de vérifier automatiquement le bon fonctionnement de l'application après une mise à jour ou une modification.

## Accès aux Tests

### Interface Web

**URL** : `/tests`

**Permissions** : Administrateurs uniquement

**Navigation** :
1. Se connecter en tant qu'administrateur
2. Aller dans le menu **Maintenance**
3. Cliquer sur **Tests Unitaires**

## Tests Disponibles

### 1. Test de Configuration

**Description** : Vérifie le système de configuration et les paramètres

**Vérifications** :
- ✓ Paramètres présents en base de données
- ✓ Nombre de catégories correct (7 catégories)
- ✓ Fonction `config()` fonctionnelle
- ✓ Fallback sur valeur par défaut
- ✓ Paramètres critiques non nuls (app.url, validation.mode, etc.)
- ✓ Types de paramètres corrects (integer, string, boolean)

**Nombre de tests** : 8

### 2. Test de Base de Données

**Description** : Vérifie la connexion et l'intégrité de la base de données

**Vérifications** :
- ✓ Connexion à la base de données
- ✓ Présence de toutes les tables requises (12 tables)
- ✓ Données minimales présentes (rôles, supports, paramètres)
- ✓ Foreign key checks activés

**Nombre de tests** : 17

**Tables vérifiées** :
- users, roles, user_roles
- campaigns, campaign_supports, campaign_validators
- supports, settings
- validations, validation_tokens
- campaign_logs, files

## Utilisation

### Exécuter Tous les Tests

1. Accéder à `/tests`
2. Cliquer sur **Exécuter tous les tests**
3. Consulter les résultats détaillés

**Affichage** :
- Résumé global (tests réussis/échoués, taux de réussite)
- Détail par test avec indicateur visuel (✓ ou ✗)
- Messages d'erreur en cas d'échec

### Exécuter un Test Spécifique

1. Accéder à `/tests`
2. Cliquer sur **Exécuter ce test** sur la carte du test souhaité
3. Consulter les résultats

## Interpréter les Résultats

### Test Réussi (✓)

- **Icône** : Cercle vert avec coche
- **Signification** : La vérification est OK
- **Action** : Aucune

### Test Échoué (✗)

- **Icône** : Cercle rouge avec croix
- **Signification** : Un problème a été détecté
- **Action requise** : Consulter le message d'erreur et corriger

**Exemples de messages** :
- "Aucun paramètre trouvé en base" → Exécuter la migration des paramètres
- "Table 'campaigns' n'existe pas" → Vérifier la structure de la base de données
- "Connexion à la base de données" → Vérifier les identifiants dans config/database.php

### Taux de Réussite

- **100%** (vert) : Tout fonctionne parfaitement
- **80-99%** (orange) : Quelques problèmes mineurs
- **< 80%** (rouge) : Problèmes importants nécessitant une attention immédiate

## Quand Exécuter les Tests

### Recommandations

1. **Après une mise à jour** : Vérifier que tout fonctionne toujours
2. **Après une modification de la base de données** : S'assurer de l'intégrité
3. **Avant un déploiement en production** : Validation finale
4. **Périodiquement** : Vérification de routine (hebdomadaire ou mensuelle)

### Automatisation (Futur)

Dans une version future, les tests pourront être exécutés automatiquement :
- Via CRON (quotidien)
- Avec envoi d'email en cas d'échec
- Intégration dans un pipeline CI/CD

## Créer un Nouveau Test

### Structure d'un Test

```php
<?php

namespace Agora\Tests;

require_once __DIR__ . '/TestRunner.php';

use Agora\Services\Database;

class MonTest extends TestRunner
{
    private $db;

    public function __construct(Database $db)
    {
        parent::__construct();
        $this->db = $db;
    }

    protected function getTestName(): string
    {
        return "Mon Test";
    }

    public function run(): array
    {
        // Test 1: Vérification simple
        $this->assertTrue(
            1 + 1 === 2,
            "Addition simple",
            "1 + 1 devrait égaler 2"
        );

        // Test 2: Vérification base de données
        $count = $this->db->fetch("SELECT COUNT(*) as count FROM users");
        $this->assertTrue(
            $count['count'] > 0,
            "Utilisateurs présents",
            "Aucun utilisateur trouvé"
        );

        // Retourner les résultats
        return $this->getResults();
    }
}
```

### Méthodes d'Assertion

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `assertTrue($value, $name, $failMsg)` | Vérifie qu'une valeur est vraie | `$this->assertTrue($user !== null, "Utilisateur trouvé")` |
| `assertFalse($value, $name, $failMsg)` | Vérifie qu'une valeur est fausse | `$this->assertFalse($errors, "Pas d'erreur")` |
| `assertEquals($exp, $act, $name)` | Vérifie l'égalité | `$this->assertEquals(10, $count, "10 éléments")` |
| `assertNotEquals($exp, $act, $name)` | Vérifie la différence | `$this->assertNotEquals(0, $id, "ID non nul")` |
| `assertNotNull($value, $name)` | Vérifie non-null | `$this->assertNotNull($config, "Config chargée")` |
| `assertNotEmpty($value, $name)` | Vérifie non-vide | `$this->assertNotEmpty($array, "Tableau non vide")` |
| `assertCount($exp, $array, $name)` | Vérifie le nombre d'éléments | `$this->assertCount(5, $roles, "5 rôles")` |

### Enregistrer le Test

Éditer `src/Controllers/TestsController.php`, méthode `getAvailableTests()` :

```php
[
    'name' => 'mon-test',
    'label' => 'Mon Test',
    'description' => 'Description de ce que fait le test',
    'icon' => 'fa-check',  // Icône Font Awesome
    'class' => 'Agora\\Tests\\MonTest',
    'file' => __DIR__ . '/../../tests/MonTest.php',
],
```

Le test apparaîtra automatiquement dans l'interface `/tests`.

## Bonnes Pratiques

### DO ✓

- Tests rapides (< 1 seconde par test)
- Tests indépendants (pas de dépendance entre tests)
- Lecture seule (pas de modification de données)
- Messages d'erreur clairs et descriptifs
- Un test = une fonctionnalité spécifique

### DON'T ✗

- Modifier les données en base
- Créer des dépendances entre tests
- Tests trop lents (> 5 secondes)
- Messages d'erreur vagues ("Erreur", "Test échoué")
- Tester plusieurs fonctionnalités dans un seul test

## Exemples de Tests

### Vérifier une Configuration

```php
$mode = config('validation.mode');
$this->assertEquals('passerelle', $mode, "Mode de validation");
```

### Vérifier une Table

```php
$result = $this->db->fetch("SHOW TABLES LIKE 'campaigns'");
$this->assertNotNull($result, "Table 'campaigns' existe");
```

### Vérifier des Données

```php
$count = $this->db->fetch("SELECT COUNT(*) as count FROM roles");
$this->assertTrue($count['count'] >= 4, "Au moins 4 rôles définis");
```

### Vérifier une Fonctionnalité

```php
$url = config('app.url');
$this->assertTrue(
    filter_var($url, FILTER_VALIDATE_URL) !== false,
    "URL valide",
    "L'URL de l'application n'est pas valide"
);
```

## Dépannage

### Erreur : "Class not found"

**Cause** : Le fichier de test n'est pas chargé correctement

**Solution** : Vérifier le chemin dans `getAvailableTests()` et que le fichier existe

### Erreur : "Call to undefined method"

**Cause** : Méthode d'assertion inexistante

**Solution** : Utiliser une des méthodes listées dans TestRunner.php

### Tests qui échouent après une mise à jour

**Cause** : Structure de la base ou configuration modifiée

**Solution** :
1. Vérifier les messages d'erreur
2. Adapter les tests à la nouvelle structure
3. Ou corriger les données/configuration

## Ressources

- [Fichier TestRunner.php](../tests/TestRunner.php) : Classe de base
- [Fichier ConfigTest.php](../tests/ConfigTest.php) : Exemple de test
- [Fichier DatabaseTest.php](../tests/DatabaseTest.php) : Exemple de test
- [README tests/](../tests/README.md) : Documentation complète

---

**Version** : 1.0
**Dernière mise à jour** : 19 octobre 2025
