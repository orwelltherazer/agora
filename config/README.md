# Répertoire config/

## Fichiers de Configuration

### ✅ database.php - ACTIF

**Description** : Configuration de la connexion à la base de données MySQL

**Utilisation** : Ce fichier est **INDISPENSABLE** et doit être configuré lors de l'installation

**Paramètres** :
- `host` : Serveur MySQL (généralement `localhost`)
- `database` : Nom de la base de données
- `username` : Utilisateur MySQL
- `password` : Mot de passe MySQL
- `charset` : Encodage (utf8mb4 recommandé)

**Modifications** : Éditer directement ce fichier

---

### ⚠️ app.php - FALLBACK UNIQUEMENT

**Description** : Configuration de fallback de sécurité

**Important** : Ce fichier sert uniquement de **fallback** si la base de données est inaccessible.

**Paramètres réels** : Tous les paramètres de configuration sont maintenant dans la **base de données** et sont gérables via l'interface web :

👉 **Accéder à** : `/settings` (réservé aux administrateurs)

**Catégories disponibles** :
- Application (nom, URL, timezone, debug, etc.)
- Sécurité (sessions, cookies)
- Fichiers (taille max upload, types autorisés)
- Pagination
- Notifications
- Validation (mode direct/passerelle, URL passerelle, clé API)
- Email (SMTP : host, port, username, password, etc.)

**Quand modifier ce fichier** : Uniquement pour :
- Chemin d'upload (`upload.path`)
- URL de base (`url`) - si changement de serveur
- Timezone (`timezone`)

**Backup** : Une sauvegarde de l'ancien fichier est dans `app.php.backup`

---

### ❌ mail.php - OBSOLÈTE

**Description** : Ancienne configuration email SMTP

**Statut** : **OBSOLÈTE** - Les paramètres email sont maintenant dans la base de données

**Remplacement** : Accédez à `/settings` > Onglet "Email (SMTP)"

**Paramètres à configurer dans l'interface web** :
- Serveur SMTP (`email_smtp_host`)
- Port SMTP (`email_smtp_port`)
- Nom d'utilisateur (`email_smtp_username`)
- Mot de passe (`email_smtp_password`)
- Chiffrement (`email_smtp_encryption`)
- Email expéditeur (`email_from_address`)
- Nom expéditeur (`email_from_name`)

**Action recommandée** : Ce fichier peut être supprimé

---

## Migration des Paramètres

### Ancienne méthode (obsolète)

```php
// Avant : éditer manuellement app.php ou mail.php
$mailConfig = require 'config/mail.php';
```

**Problèmes** :
- ❌ Risque d'erreur de syntaxe PHP
- ❌ Fichiers sensibles (mots de passe en clair)
- ❌ Pas de validation
- ❌ Aucune traçabilité des modifications

### Nouvelle méthode (recommandée)

```php
// Maintenant : via la base de données
$smtpHost = config('email.smtp_host');
$appName = config('app.name');
```

**Avantages** :
- ✅ Interface web sécurisée
- ✅ Pas de risque d'erreur de syntaxe
- ✅ Fallback automatique sur app.php
- ✅ Traçabilité (updated_at dans la base)
- ✅ Organisation par catégories

---

## Pour les Développeurs

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

### Charger toute la config

```php
$allConfig = config_all();
// Retourne un tableau avec toutes les valeurs (BDD + fallback app.php)
```

---

## Installation / Déploiement

### Étapes requises

1. **Configurer database.php** :
```php
return [
    'host' => 'localhost',
    'database' => 'agora',
    'username' => 'root',
    'password' => 'votre_mot_de_passe',
    // ...
];
```

2. **Importer la base de données** :
```bash
mysql -u root -p agora < database/dump.sql
```

3. **Vérifier app.php** :
- URL de base correcte
- Chemin d'upload correct

4. **Configurer les paramètres via l'interface** :
- Se connecter en tant qu'administrateur
- Aller dans `/settings`
- Configurer SMTP, validation, etc.

### Migration depuis une ancienne version

Si vous migrez depuis une version avec `mail.php` :

1. Les paramètres email sont déjà en base (migration automatique)
2. Accéder à `/settings` pour vérifier les valeurs
3. Supprimer `mail.php` (optionnel, il est ignoré)

---

## Sécurité

### Fichiers sensibles

- ✅ `database.php` : **Protégé** - Pas accessible via HTTP (hors webroot)
- ✅ `app.php` : **Protégé** - Pas accessible via HTTP (hors webroot)
- ❌ `mail.php` : **Obsolète** - À supprimer

### Base de données

Les paramètres sensibles en base :
- `validation_passerelle_api_key` : Clé API passerelle
- `email_smtp_password` : Mot de passe SMTP (stocké en clair)

**Recommandation** : Protéger l'accès à la base de données et faire des backups chiffrés

---

## Dépannage

### Erreur : "Paramètre introuvable"

```php
config('mon.parametre') // retourne null
```

**Cause** : Le paramètre n'existe ni en base, ni dans app.php

**Solution** :
1. Vérifier `/settings` dans l'interface web
2. Ou ajouter un fallback : `config('mon.parametre', 'valeur_par_defaut')`

### Erreur : "Database connection failed"

**Cause** : `database.php` mal configuré ou serveur MySQL éteint

**Solution** :
1. Vérifier `config/database.php`
2. Vérifier que MySQL est démarré
3. Tester la connexion : `mysql -u root -p`

### Les modifications dans /settings ne sont pas prises en compte

**Cause** : Cache non rafraîchi (rare)

**Solution** :
```php
ConfigService::getInstance()->refresh();
```

Ou redémarrer le serveur web.

---

## Voir aussi

- [Documentation paramètres](../docs/parametres.md)
- [Documentation fonctionnelle](../docs/fonctionnel.md)
