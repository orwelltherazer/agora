# Migration de la Configuration vers la Base de Données

## 🎯 Objectif

Centraliser tous les paramètres de configuration dans la **base de données** avec une interface web sécurisée, éliminant les risques liés à l'édition manuelle de fichiers PHP.

## ✅ Travaux Réalisés

### 1. Migration de la Base de Données

**Fichier** : `database/migrations/migrate_settings_to_db.sql`

- Ajout des colonnes `categorie` et `ordre` à la table `settings`
- Migration de 28 paramètres organisés en 7 catégories :
  - Application (nom, URL, timezone, debug, etc.)
  - Sécurité (sessions, cookies)
  - Fichiers (upload, taille max, types autorisés)
  - Pagination
  - Notifications
  - Validation (mode, passerelle, API)
  - Email (SMTP complet)

**Exécution** :
```sql
-- Charger le fichier SQL via phpMyAdmin ou :
mysql -u root -p agora < database/migrations/migrate_settings_to_db.sql
```

### 2. ConfigService - Service de Configuration

**Fichier** : `src/Services/ConfigService.php`

- **Pattern** : Singleton pour éviter les requêtes multiples
- **Fonctionnalités** :
  - Chargement automatique depuis la base de données
  - Fallback sur `config/app.php` si la BDD est indisponible
  - Support de la notation pointée (`app.name`, `email.smtp_host`)
  - Cache en mémoire pour les performances
  - Méthodes : `get()`, `set()`, `all()`, `refresh()`

**Exemple** :
```php
$configService = ConfigService::getInstance();
$appName = $configService->get('app.name');
$smtpHost = $configService->get('email.smtp_host');
```

### 3. Fonctions Helpers

**Fichier** : `src/Helpers/functions.php`

Trois fonctions globales pour faciliter l'accès :

```php
// Récupérer une valeur
$value = config('app.name', 'Default');

// Définir une valeur (rare, préférer l'interface web)
config_set('app.name', 'Mon Application');

// Récupérer toute la config
$allConfig = config_all();
```

### 4. Interface Web de Gestion

**Fichier** : `templates/settings/index.twig`

- **URL** : `/settings` (réservée aux administrateurs)
- **Interface** : 7 onglets organisés avec icônes Font Awesome
- **Types de champs** : text, number, checkbox, password
- **Sécurité** : Avertissements pour les paramètres sensibles
- **UX** : JavaScript pour navigation entre onglets, validation formulaire

**Onglets** :
1. 📱 Application
2. 🔒 Sécurité
3. 📁 Fichiers
4. 📄 Pagination
5. 🔔 Notifications
6. ✅ Validation
7. 📧 Email (SMTP)

### 5. Adaptation du Code

**Fichiers modifiés** :

#### `src/Controllers/SettingsController.php`
- Organisation des paramètres par catégorie
- Labels français pour les catégories
- Tri par ordre

#### `src/Controllers/CampaignController.php`
- Remplacement de `require config/app.php` par `config()`
- Utilisation de `config('validation.mode')`, `config('validation.passerelle_url')`, etc.

#### `src/Services/PasserelleSyncService.php`
- Suppression de la propriété `$config`
- Utilisation exclusive de `config()` pour tous les paramètres

#### `src/Services/MailService.php`
- Chargement prioritaire depuis la base via `config()`
- Fallback sur le paramètre `$config` du constructeur (pour rétrocompatibilité)
- Adaptation de `configureSMTP()` pour utiliser `config('email.*')`

#### `public/index.php`
- **SUPPRESSION** de `require 'config/mail.php'`
- Initialisation conditionnelle de MailService basée sur `config('email.smtp_username')` et `config('email.smtp_password')`
- Chargement de `src/Helpers/functions.php`

#### `src/Controllers/MaintenanceController.php`
- Fonction `testEmail()` mise à jour pour vérifier la config via `config()`
- Messages d'erreur dirigeant vers `/settings > Email (SMTP)` au lieu de `config/mail.php`

### 6. Simplification des Fichiers de Config

#### `config/app.php` - Réduit au minimum
**Avant** : 55 lignes avec tous les paramètres
**Après** : 31 lignes avec seulement les valeurs de fallback essentielles

```php
return [
    'debug' => true,
    'name' => 'Agora',
    'url' => 'http://localhost/agora/public',
    'timezone' => 'Europe/Paris',
    'upload' => [
        'path' => __DIR__ . '/../public/uploads/',
        'max_size' => 10485760,
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ai', 'psd'],
    ],
];
```

#### `config/mail.php` - Marqué comme obsolète
- Fichier vidé avec commentaires explicatifs
- Conservé uniquement pour éviter les erreurs avec d'anciens scripts
- Peut être supprimé après vérification complète

### 7. Documentation Mise à Jour

#### `config/README.md` - Créé
- Explication complète du système de configuration
- Guide migration ancienne → nouvelle méthode
- Documentation pour développeurs
- Procédure de dépannage

#### `CONFIG_EMAIL.md` - Mis à jour
- Remplacement des références à `config/mail.php`
- Instructions pour utiliser `/settings > Email (SMTP)`
- Exemples adaptés à l'interface web

#### `tests/test_email_web.php` - Mis à jour
- Chargement via `config()` au lieu de `require mail.php`
- Messages d'erreur pointant vers `/settings`
- Affichage des valeurs depuis la base de données

#### `tests/test_email.php` - Mis à jour
- Script CLI adapté pour utiliser `config()`
- Messages d'erreur mis à jour

### 8. Tests Unitaires

**Fichier** : `tests/ConfigTest.php`

Tests créés pour vérifier :
- Connexion à la base de données
- Présence de paramètres en base
- Fonction `config()` pour divers paramètres
- Fonction `config_all()`
- Singleton de ConfigService

**Accès** : `/tests` puis "Test de Configuration"

### 9. Outils de Diagnostic

#### `verify-config.php` - Script de vérification
Accessible via navigateur, affiche :
- ✅ Connexion à la base de données
- ✅ Fonction `config()` avec exemples
- ✅ Service Email configuré ou non
- ✅ Fichiers de configuration présents
- ✅ Paramètres par catégorie

**URL** : `http://localhost/agora/verify-config.php`

## 🔄 Workflow de Configuration

### Ancienne Méthode (OBSOLÈTE) ❌

```php
// Éditer manuellement config/app.php ou config/mail.php
$appConfig = require 'config/app.php';
$mailConfig = require 'config/mail.php';
$validationMode = $appConfig['validation']['mode'];
```

**Problèmes** :
- Risque d'erreur de syntaxe PHP
- Fichiers sensibles avec mots de passe en clair
- Pas de validation
- Aucune traçabilité des modifications

### Nouvelle Méthode (RECOMMANDÉE) ✅

```php
// Via la base de données avec interface web
$validationMode = config('validation.mode');
$smtpHost = config('email.smtp_host');
```

**Avantages** :
- Interface web sécurisée et validée
- Pas de risque d'erreur de syntaxe
- Traçabilité (colonne `updated_at` dans la base)
- Fallback automatique sur `config/app.php`
- Organisation par catégories

## 📊 Statistiques

- **28 paramètres** migrés vers la base de données
- **7 catégories** organisées
- **3 fichiers** de configuration (database.php, app.php simplifiée, mail.php obsolète)
- **8 fichiers** de code modifiés
- **4 fichiers** de documentation créés/mis à jour
- **1 service** créé (ConfigService)
- **3 fonctions** helpers ajoutées

## 🔒 Sécurité

### Fichiers Protégés
- ✅ `config/database.php` : Hors webroot, pas accessible via HTTP
- ✅ `config/app.php` : Hors webroot, fallback seulement
- ⚠️ `config/mail.php` : Obsolète, peut être supprimé

### Base de Données
Les paramètres sensibles sont stockés en base :
- `validation_passerelle_api_key` : Clé API passerelle
- `email_smtp_password` : Mot de passe SMTP

**Recommandation** :
- Protéger l'accès à la base de données
- Faire des backups chiffrés
- Limiter l'accès à `/settings` aux administrateurs (déjà implémenté)

## 🚀 Déploiement

### Installation Nouvelle Instance

1. **Configurer `database.php`** :
```php
return [
    'host' => 'localhost',
    'database' => 'agora',
    'username' => 'root',
    'password' => 'votre_mot_de_passe',
];
```

2. **Importer la base de données** (avec migration déjà incluse) :
```bash
mysql -u root -p agora < database/dump.sql
```

3. **Vérifier `app.php`** :
- URL de base correcte
- Chemin d'upload correct
- Timezone

4. **Configurer via l'interface web** :
- Se connecter en admin
- Aller dans `/settings`
- Configurer SMTP, validation, etc.

### Migration Depuis Ancienne Version

Si vous aviez une version avec `mail.php` :

1. Les paramètres sont déjà migrés (via SQL)
2. Accéder à `/settings` pour vérifier
3. **Optionnel** : Supprimer `config/mail.php`

## 📝 Pour les Développeurs

### Accéder à un paramètre

```php
// Notation pointée
$value = config('categorie.parametre');

// Exemples
$appUrl = config('app.url');
$smtpHost = config('email.smtp_host');
$validationMode = config('validation.mode');

// Avec valeur par défaut
$debug = config('app.debug', false);
```

### Modifier un paramètre (rare)

```php
// Par code (déconseillé, préférer l'interface web)
config_set('app.name', 'Mon Application');
```

### Ajouter un nouveau paramètre

1. Via l'interface `/settings` (si le paramètre existe déjà)
2. Via SQL (pour un nouveau paramètre) :

```sql
INSERT INTO settings (cle, categorie, ordre, valeur, type, description)
VALUES ('mon_parametre', 'application', 10, 'valeur_par_defaut', 'string', 'Description du paramètre');
```

3. Utiliser dans le code :
```php
$value = config('mon_parametre');
```

## 🔍 Dépannage

### Les modifications dans /settings ne sont pas prises en compte

**Cause** : Cache non rafraîchi (rare)

**Solution** :
```php
ConfigService::getInstance()->refresh();
```

Ou redémarrer le serveur web.

### Erreur : "Database connection failed"

**Cause** : `database.php` mal configuré ou MySQL éteint

**Solution** :
1. Vérifier `config/database.php`
2. Vérifier que MySQL est démarré
3. Tester : `mysql -u root -p`

### Paramètre non trouvé

**Cause** : Le paramètre n'existe ni en base, ni dans `app.php`

**Solution** :
1. Vérifier dans `/settings`
2. Ajouter un fallback : `config('mon.parametre', 'valeur_par_defaut')`
3. Ou ajouter en base via SQL

## 📚 Voir Aussi

- [Documentation Paramètres](docs/parametres.md)
- [Documentation Fonctionnelle](docs/fonctionnel.md)
- [Configuration Email](CONFIG_EMAIL.md)
- [Config README](config/README.md)

---

**Date de migration** : 2025-10-19
**Statut** : ✅ Terminé et testé
