# Gestion des Paramètres de Configuration

## Vue d'ensemble

Agora dispose d'un système de gestion de paramètres flexible et sécurisé qui permet de configurer l'application via une interface web plutôt que par édition de fichiers PHP.

### Avantages

- **Sécurité** : Pas de risque d'erreur de syntaxe PHP qui rendrait l'application inaccessible
- **Interface conviviale** : Modification via formulaire web avec onglets organisés
- **Traçabilité** : Les modifications sont horodatées dans la base de données
- **Validation** : Types de données validés (string, integer, boolean, password)
- **Fallback** : Le fichier `config/app.php` reste utilisé comme sécurité en cas de problème

## Architecture

### Stockage en Base de Données

Les paramètres sont stockés dans la table `settings` :

```sql
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cle VARCHAR(100) UNIQUE NOT NULL,        -- Ex: 'app_name', 'validation_mode'
    categorie VARCHAR(50) DEFAULT 'general',  -- Ex: 'application', 'validation', 'email'
    ordre INT DEFAULT 0,                      -- Ordre d'affichage dans l'onglet
    valeur TEXT,                              -- Valeur du paramètre
    type ENUM('string','integer','boolean','password','json') DEFAULT 'string',
    description TEXT,                         -- Description affichée dans l'interface
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Catégories de Paramètres

Les paramètres sont organisés en 7 catégories :

1. **Application** : Nom, version, URL, timezone, locale
2. **Sécurité** : Configuration des sessions, cookies
3. **Fichiers** : Taille max upload, types autorisés
4. **Pagination** : Nombre d'éléments par page
5. **Notifications** : Délais de relance, alertes
6. **Validation** : Mode (direct/passerelle), URL passerelle, clé API
7. **Email (SMTP)** : Configuration serveur mail

## Migration depuis app.php

### Étape 1 : Exécuter la Migration

La migration peuple automatiquement la base de données avec tous les paramètres actuels de `config/app.php` :

```bash
# Windows
php bin\migrate-settings.php

# Linux/Mac
php bin/migrate-settings.php
```

**Sortie attendue :**
```
==============================================
Migration des paramètres vers la base de données
==============================================

[INFO] Connexion à la base de données... OK
[INFO] Lecture du fichier de migration... OK
[INFO] Nombre de requêtes à exécuter : 45

...........................

==============================================
Migration terminée
==============================================
Requêtes réussies : 45
Requêtes en erreur : 0

[INFO] Nombre de paramètres dans la base : 25

✓ Vous pouvez maintenant accéder à /settings pour gérer vos paramètres via l'interface web.
✓ Les paramètres seront lus depuis la base de données en priorité.
✓ Le fichier config/app.php reste utilisé comme fallback de sécurité.
```

### Étape 2 : Vérifier l'Interface Web

Accédez à **Paramètres** dans le menu d'administration (réservé aux administrateurs).

Vous verrez une interface à onglets avec tous les paramètres organisés par catégorie.

## Utilisation dans le Code

### Fonction `config()`

La fonction helper `config()` charge les paramètres avec cette logique :

1. **Priorité 1** : Lire depuis la base de données
2. **Fallback** : Si absent ou erreur, lire depuis `config/app.php`

```php
// Lecture de paramètres (notation pointée)
$appName = config('app.name');                    // "Agora"
$validationMode = config('validation.mode');       // "passerelle"
$tokenExpiry = config('validation.token_expiry_days', 30);  // 30 par défaut

// Lecture avec valeur par défaut
$debug = config('app.debug', false);
```

### Fonction `config_set()`

Permet de modifier un paramètre par code (rare, généralement on utilise l'interface web) :

```php
// Mettre à jour un paramètre
config_set('app.name', 'Mon Application');
config_set('validation.mode', 'direct');
```

### Fonction `config_all()`

Récupère toute la configuration sous forme de tableau :

```php
$config = config_all();
// [
//     'app' => ['name' => 'Agora', 'url' => '...'],
//     'validation' => ['mode' => 'passerelle', ...],
//     ...
// ]
```

### Service ConfigService

Pour un usage avancé, vous pouvez utiliser directement le service :

```php
use Agora\Services\ConfigService;

$configService = ConfigService::getInstance();

// Lecture
$value = $configService->get('app.name');

// Écriture
$configService->set('app.name', 'Nouvelle Valeur');

// Rafraîchir le cache
$configService->refresh();
```

## Interface Web

### Accès

- **URL** : `/settings`
- **Permissions** : Administrateurs uniquement

### Fonctionnalités

#### Onglets par Catégorie

L'interface présente 7 onglets correspondant aux 7 catégories de paramètres. Cliquer sur un onglet affiche les paramètres de cette catégorie.

#### Types de Champs

- **String** : Champ texte simple
- **Integer** : Champ numérique
- **Boolean** : Case à cocher (Activé/Désactivé)
- **Password** : Champ mot de passe masqué

#### Descriptions

Chaque paramètre affiche :
- Un libellé lisible (ex : "Nom", "Mode", "Clé API")
- Une description explicative (ex : "Durée de validité des tokens en jours")

#### Sauvegarde

Bouton **"Enregistrer les modifications"** en bas de page :
- Sauvegarde TOUS les paramètres (de tous les onglets)
- Redirection automatique après enregistrement
- Confirmation visuelle

#### Avertissement de Sécurité

Un encadré jaune en bas de page rappelle :
- Les paramètres contiennent des informations sensibles
- La clé API doit être changée en production
- Le mot de passe SMTP est stocké en clair
- Les cookies sécurisés doivent être activés en production

## Exemples d'Utilisation

### Exemple 1 : Changer le Mode de Validation

**Avant (édition manuelle de app.php)** :
```php
// config/app.php
'validation' => [
    'mode' => 'passerelle',  // Risque d'erreur de syntaxe !
    // ...
]
```

**Après (interface web)** :
1. Aller sur `/settings`
2. Cliquer sur l'onglet **"Validation"**
3. Modifier le champ **"Mode"** : choisir `direct` ou `passerelle`
4. Cliquer sur **"Enregistrer"**

✓ Aucun risque d'erreur de syntaxe
✓ Modification immédiate

### Exemple 2 : Configurer le SMTP

**Interface web** :
1. Aller sur `/settings`
2. Cliquer sur l'onglet **"Email (SMTP)"**
3. Remplir les champs :
   - **Serveur SMTP** : `smtp.gmail.com`
   - **Port** : `587`
   - **Nom d'utilisateur** : `mon-email@gmail.com`
   - **Mot de passe** : `mon-mot-de-passe`
   - **Chiffrement** : `tls`
   - **Email expéditeur** : `noreply@mondomaine.com`
   - **Nom expéditeur** : `Agora`
4. Enregistrer

**Vérifier** :
- Aller sur **Maintenance > Test d'email**
- Envoyer un email de test

### Exemple 3 : Augmenter la Taille Max des Fichiers

1. Aller sur `/settings`
2. Onglet **"Fichiers"**
3. **Taille maximale** : changer de `10485760` (10 Mo) à `52428800` (50 Mo)
4. Enregistrer

⚠️ **Important** : Vérifiez aussi les limites PHP :
- `upload_max_filesize` dans `php.ini`
- `post_max_size` dans `php.ini`

## Sécurité

### Paramètres Sensibles

Certains paramètres contiennent des données sensibles :

| Paramètre | Sensibilité | Recommandation |
|-----------|-------------|----------------|
| `validation_passerelle_api_key` | ⚠️ Très élevée | Générer une clé unique et complexe en production |
| `email_smtp_password` | ⚠️ Très élevée | Stocké en clair dans la base - protéger l'accès base |
| `session_secure` | ⚠️ Élevée | TOUJOURS activer en production (HTTPS obligatoire) |
| `app_debug` | ⚠️ Moyenne | TOUJOURS désactiver en production |

### Bonnes Pratiques

1. **En Production** :
   - Activer `session_secure` (requiert HTTPS)
   - Désactiver `app_debug`
   - Changer `validation_passerelle_api_key` (minimum 32 caractères)
   - Utiliser un mot de passe SMTP fort

2. **Accès à l'Interface** :
   - Réservé aux administrateurs uniquement
   - Pas d'accès public

3. **Sauvegarde** :
   - Faire un dump SQL de la table `settings` avant modification importante
   - Conserver `config/app.php` comme référence

4. **Base de Données** :
   - Protéger l'accès à la base de données
   - Utiliser un utilisateur MySQL avec privilèges limités
   - Chiffrer les backups

### Fallback de Sécurité

En cas de problème avec la base de données (corruption, crash, etc.), l'application continue de fonctionner en lisant `config/app.php` :

```php
// Si la base est inaccessible :
config('app.name')  // → Lit depuis config/app.php

// Si un paramètre n'existe pas en base :
config('nouvelle.option', 'default')  // → Retourne 'default'
```

## Dépannage

### Problème : Les modifications ne sont pas prises en compte

**Cause possible** : Cache non rafraîchi

**Solution** :
```php
// Dans le code
ConfigService::getInstance()->refresh();

// Ou redémarrer le serveur web
```

### Problème : Erreur "Table settings not found"

**Cause** : La migration n'a pas été exécutée

**Solution** :
```bash
php bin/migrate-settings.php
```

### Problème : Impossible d'accéder à /settings

**Cause possible** : Utilisateur non administrateur

**Vérification** :
```sql
-- Vérifier les rôles de l'utilisateur
SELECT u.email, r.nom
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE u.email = 'votre@email.com';
```

**Solution** : Donner le rôle Administrateur à l'utilisateur

### Problème : Valeur par défaut incorrecte

**Cause** : Type de données incorrect dans la base

**Solution** : Corriger le type dans la table settings
```sql
-- Exemple : changer un boolean mal typé
UPDATE settings
SET type = 'boolean', valeur = '1'
WHERE cle = 'app_debug';
```

## Migration Inverse (Retour à app.php uniquement)

Si vous souhaitez revenir à l'ancien système (déconseillé) :

1. **Export des valeurs actuelles**
```bash
php bin/export-settings-to-php.php > config/app_generated.php
```

2. **Désactiver le système de config**
Remplacer les appels `config()` par lecture directe de `$appConfig`

3. **Supprimer les colonnes ajoutées** (optionnel)
```sql
ALTER TABLE settings DROP COLUMN categorie;
ALTER TABLE settings DROP COLUMN ordre;
```

## Évolutions Futures

### Prévues

- **Interface de comparaison** : Comparer config actuelle avec app.php
- **Historique des modifications** : Qui a changé quoi et quand
- **Import/Export** : Exporter/importer la config entre environnements
- **Validation avancée** : Règles de validation par paramètre (regex, plage, etc.)
- **Chiffrement** : Chiffrer les paramètres sensibles (mot de passe SMTP, clé API)

### Suggestions Bienvenues

Proposer des améliorations via les issues du projet.

## Référence des Paramètres

### Catégorie : Application

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `app_name` | string | "Agora" | Nom de l'application |
| `app_description` | string | "Gestion des communications" | Description |
| `app_version` | string | "1.0.0" | Version (lecture seule) |
| `app_url` | string | "http://localhost/agora/public" | URL de base |
| `app_timezone` | string | "Europe/Paris" | Fuseau horaire |
| `app_locale` | string | "fr_FR" | Langue |
| `app_debug` | boolean | true | Mode debug |

### Catégorie : Sécurité

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `session_name` | string | "AGORA_SESSION" | Nom du cookie session |
| `session_lifetime` | integer | 7200 | Durée session (secondes) |
| `session_secure` | boolean | false | Cookies HTTPS only |
| `session_httponly` | boolean | true | Protection XSS |
| `session_samesite` | string | "Lax" | Politique SameSite |

### Catégorie : Fichiers

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `upload_max_size` | integer | 10485760 | Taille max (octets) |
| `upload_allowed_types` | string | "jpg,jpeg,png,pdf,..." | Extensions autorisées |

### Catégorie : Pagination

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `pagination_per_page` | integer | 20 | Éléments par page |

### Catégorie : Notifications

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `notif_relance_delai_jours` | integer | 5 | Délai avant relance (jours) |
| `notif_deadline_alerte_jours` | integer | 7 | Alerte échéance (jours) |

### Catégorie : Validation

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `validation_mode` | string | "passerelle" | Mode : direct ou passerelle |
| `validation_passerelle_url` | string | "https://..." | URL passerelle |
| `validation_passerelle_api_key` | string | "CHANGE_ME..." | Clé API (à changer!) |
| `validation_token_expiry_days` | integer | 30 | Validité tokens (jours) |

### Catégorie : Email (SMTP)

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `email_smtp_host` | string | "" | Serveur SMTP |
| `email_smtp_port` | integer | 587 | Port SMTP |
| `email_smtp_username` | string | "" | Utilisateur SMTP |
| `email_smtp_password` | password | "" | Mot de passe SMTP |
| `email_smtp_encryption` | string | "tls" | Chiffrement (tls/ssl) |
| `email_from_address` | string | "noreply@example.com" | Email expéditeur |
| `email_from_name` | string | "Agora" | Nom expéditeur |

---

**Version du document** : 1.0
**Date** : 19 octobre 2025
**Auteur** : Documentation Agora
