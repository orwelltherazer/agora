# Contexte Complet du Projet AGORA pour Reprise par IA

## Prompt de Reprise (à copier-coller)

```
Je travaille sur une application PHP appelée AGORA - un système de gestion de campagnes de communication pour une collectivité locale.

**Environnement technique:**
- PHP 7.3 (important : pas de syntaxe PHP 8.0+, pas de union types, pas d'arrow functions)
- MySQL pour la base principale Agora
- SQLite pour la base passerelle
- Architecture MVC avec Twig pour les templates
- Localisation : c:\xampp2\htdocs\agora
- URL de base : http://localhost/agora/public/
- Pas de git configuré

**Architecture:**
- src/Controllers/ - Contrôleurs MVC
- src/Services/ - Services (Database, Mail, etc.)
- src/Models/ - Modèles (peu utilisés, on utilise surtout Database directement)
- src/Repositories/ - Repositories pour requêtes complexes
- templates/ - Vues Twig
- public/ - Point d'entrée web
- passerelle/ - Système externe de validation (SQLite)
- tests/ - Scripts de test et vérification

**Système de configuration unique:**
La base de données contient une table `settings` avec des clés/valeurs.
La fonction `config()` (dans src/Helpers/functions.php) transforme les underscores en hiérarchie :
- `email_host` en base → accessible via `config('email.host')`
- IMPORTANT: Éviter les multiples underscores (ex: email_smtp_host devient email.smtp.host avec 3 niveaux, ne fonctionne pas)
- Règle: Maximum 2 niveaux = 1 underscore (categorie_cle)

**Système de validation par passerelle (CRITIQUE):**
L'application a un système de validation externe pour permettre aux validateurs de valider/refuser des campagnes depuis l'extérieur de l'intranet.

Workflow complet:
1. Création campagne → génère tokens de validation (table validation_tokens)
2. Envoi emails aux validateurs avec URLs uniques
3. Validateur clique URL → redirigé vers passerelle (passerelle/validate.php)
4. Formulaire validation → enregistré dans SQLite (passerelle/validation_queue.db)
5. Synchronisation (cron ou manuel) → import dans MySQL Agora
6. Token marqué comme utilisé (used_at), validation dans table validations

Base Agora (MySQL):
- validation_tokens: token, campaign_id, user_id, expires_at, used_at
- validations: campaign_id, user_id, action (valide/refuse), commentaire

Base Passerelle (SQLite):
- validation_responses: token, campaign_id, user_id, action, commentaire, validated_at, synced_at

Fichiers clés:
- src/Controllers/CampaignController.php - ligne 390-443 : sendValidationEmails()
- src/Services/PasserelleSyncService.php - synchronisation complète
- src/Services/ValidationTokenService.php - génération tokens
- passerelle/validate.php - formulaire public
- passerelle/api.php - endpoints: /health, /pending-validations, /stats
- sync-passerelle.php - script de synchronisation (CLI ou web)

Configuration:
- validation_mode: "passerelle" ou "direct"
- validation_url: URL de la passerelle (ex: http://localhost/agora/passerelle)
- validation_apikey: clé API pour sécuriser les appels
- validation_tokendays: durée de validité des tokens (30 jours par défaut)

**Migrations de clés effectuées:**
Pour éviter les problèmes de multiples underscores:

Email:
- email_smtp_host → email_host
- email_smtp_port → email_port
- email_smtp_username → email_username
- email_smtp_password → email_password
- email_smtp_encryption → email_encryption
- email_from_address → email_fromaddress
- email_from_name → email_fromname

Validation:
- validation_passerelle_url → validation_url
- validation_passerelle_api_key → validation_apikey
- validation_token_expiry_days → validation_tokendays

**Bug important corrigé:**
Database::update() utilise des paramètres nommés (:column) pour les données.
Le WHERE doit aussi utiliser des paramètres nommés, pas positionnels:
- ❌ FAUX: `$db->update('table', ['col' => 'val'], 'id = ?', [$id])`
- ✅ BON: `$db->update('table', ['col' => 'val'], 'id = :id_where', ['id_where' => $id])`

**Table validation_tokens:**
- Utilise `used_at` (DATETIME NULL), PAS de colonne `used`
- Vérifier si utilisé: `WHERE used_at IS NULL`
- Marquer comme utilisé: `UPDATE ... SET used_at = NOW()`

**Scripts de test disponibles:**
- tests/check_database.php - Contenu base Agora
- tests/check_passerelle_db.php - Contenu base passerelle
- tests/get_validation_urls.php - URLs de validation disponibles
- tests/test_validation_form.php - Simulation validation auto
- tests/verify_complete_workflow.php - Rapport complet

**Interface de maintenance:**
URL: http://localhost/agora/public/maintenance
- Test email (fonctionne)
- Test passerelle (5 tests complets + bouton sync manuel)
- Autres outils admin

**Derniers tests effectués:**
Le workflow complet de validation a été testé avec succès le 2025-10-19:
- Campagne ID 100 "test de campagne" créée
- 3 validateurs assignés, 3 tokens générés
- 1 validation effectuée via passerelle (Jean Dupont)
- Synchronisation réussie vers Agora
- Tout fonctionne parfaitement

Voir docs/WORKFLOW_TEST_RESULTS.md pour les détails complets.

**Conventions de code:**
- Toujours require 'vendor/autoload.php' en premier
- Utiliser $db->fetch() pour 1 résultat, fetchAll() pour plusieurs
- Les templates Twig sont dans templates/
- Utiliser Auth::requireAuth() pour protéger les routes
- Sessions pour l'authentification (user_id, is_admin)
- Logs dans campaign_logs avec CampaignLogService

**Points d'attention:**
1. Toujours vérifier la compatibilité PHP 7.3
2. Ne jamais utiliser plusieurs underscores dans les clés settings
3. Toujours utiliser paramètres nommés avec Database::update()
4. La passerelle doit avoir son .htaccess pour les routes propres
5. Les tokens expirent (vérifier expires_at > NOW())

Qu'est-ce que tu veux que je fasse sur cette application ?
```

## Documentation Complète

### 1. Structure de la Base de Données

#### Tables Principales

**campaigns**
```sql
id, titre, description, demandeur, demandeur_email,
date_event_debut, date_event_fin, date_campagne_debut, date_campagne_fin,
statut (brouillon|en_validation|validee|refusee|publiee|archivee),
created_by, created_at, updated_at, archived_at
```

**users**
```sql
id, nom, prenom, email, password, actif, is_admin,
created_at, updated_at
```

**campaign_validators**
```sql
id, campaign_id, user_id, ordre, created_at
-- Ordre détermine l'ordre de validation
```

**validation_tokens**
```sql
id, campaign_id, user_id, token (SHA256),
expires_at, used_at (DATETIME NULL), created_at
-- used_at IS NULL = token non utilisé
-- used_at IS NOT NULL = token déjà utilisé
```

**validations**
```sql
id, campaign_id, user_id, action (valide|refuse),
commentaire (TEXT NULL), validated_at, created_at
```

**campaign_supports**
```sql
id, campaign_id, support_id
-- Liaison campagne <-> supports (Facebook, Intranet, etc.)
```

**supports**
```sql
id, nom, type, description, actif, ordre_affichage,
created_at, updated_at
```

**files**
```sql
id, campaign_id, nom_original, nom_stockage, chemin, type_mime,
taille, uploaded_by, est_version_actuelle (BOOLEAN),
uploaded_at, created_at
-- Fichiers stockés dans storage/uploads/YYYY/MM/
```

**campaign_logs**
```sql
id, campaign_id, user_id, action,
details (TEXT NULL), created_at
-- Log de tous les événements d'une campagne
```

**settings**
```sql
id, cle (VARCHAR UNIQUE), valeur (TEXT),
description (TEXT NULL), created_at, updated_at
```

#### Base Passerelle (SQLite)

Fichier: `passerelle/validation_queue.db`

**validation_responses**
```sql
id INTEGER PRIMARY KEY AUTOINCREMENT,
token TEXT NOT NULL,
campaign_id INTEGER NOT NULL DEFAULT 0,  -- Rempli lors de la sync
user_id INTEGER NOT NULL DEFAULT 0,      -- Rempli lors de la sync
action TEXT NOT NULL,                    -- 'valide' ou 'refuse'
commentaire TEXT,
validated_at TEXT NOT NULL,              -- datetime('now')
synced_at TEXT,                          -- datetime quand synchronisé
UNIQUE(token)
```

### 2. Services Importants

#### Database Service (src/Services/Database.php)

```php
// Méthodes principales
fetch($sql, $params = [])        // 1 résultat
fetchAll($sql, $params = [])     // Tous les résultats
insert($table, $data)            // INSERT, retourne lastInsertId
update($table, $data, $where, $whereParams) // UPDATE
delete($table, $where, $params)  // DELETE
query($sql, $params)             // Requête brute, retourne PDOStatement
```

**IMPORTANT**: `update()` utilise des paramètres nommés:
```php
// Les données utilisent :column
// Le WHERE doit utiliser :param, PAS ?
$db->update('users',
    ['nom' => 'Dupont'],          // SET nom = :nom
    'id = :id_where',              // WHERE id = :id_where
    ['id_where' => 5]
);
```

#### ConfigService (src/Services/ConfigService.php)

Charge les settings depuis la base de données.

**Transformation automatique:**
```php
// En base: cle = "email_host", valeur = "smtp.example.com"
config('email.host') // Retourne "smtp.example.com"

// Le système transforme:
// email_host → ['email']['host']
// validation_url → ['validation']['url']
```

**Limitation critique:**
- Maximum 2 niveaux = 1 underscore
- ❌ `email_smtp_host` → ['email']['smtp']['host'] (3 niveaux, ne marche PAS)
- ✅ `email_host` → ['email']['host'] (2 niveaux, OK)

#### MailService (src/Services/MailService.php)

Utilise PHPMailer pour envoyer des emails.

**Configuration requise** (dans settings):
```
email_host = smtp.example.com
email_port = 587
email_username = user@example.com
email_password = ****
email_encryption = tls
email_fromaddress = noreply@example.com
email_fromname = Agora
```

**Méthodes:**
```php
sendValidationRequest($campaign, $validator, $validationUrl, $files)
sendTestEmail($to, $subject, $body)
```

#### PasserelleSyncService (src/Services/PasserelleSyncService.php)

Service de synchronisation entre passerelle (SQLite) et Agora (MySQL).

**Méthode principale:**
```php
synchronize() : array
// Retourne ['success' => bool, 'synced' => int, 'message' => string, 'warnings' => array]
```

**Processus:**
1. Appelle API passerelle `/api.php/pending-validations`
2. Pour chaque validation:
   - Enrichit avec campaign_id/user_id depuis le token
   - Vérifie que le token existe et est valide
   - Insère dans table `validations`
   - Marque token comme utilisé (used_at = NOW())
   - Log l'événement
   - Notifie la passerelle (POST `/api.php/sync-completed`)
3. Vérifie le statut de chaque campagne (tous validé? refusé?)

**Bugs à éviter:**
- Ligne 210: Utiliser paramètre nommé pour le WHERE
- Toujours vérifier `used_at IS NULL` pas `used = 0`

#### ValidationTokenService (src/Services/ValidationTokenService.php)

Génère et gère les tokens de validation.

**Méthode principale:**
```php
generateToken($campaignId, $userId, $expiryDays = 30) : string
// Génère un token SHA256 unique
// Insère dans validation_tokens
// Retourne le token
```

**Format du token:**
```php
$data = $campaignId . $userId . time() . random_bytes(32);
$token = hash('sha256', $data);
// Exemple: a3f77766157428231a7bd6032be338be042ab29f0ed5ef77342133beb4d46dd5
```

### 3. Contrôleurs Importants

#### CampaignController (src/Controllers/CampaignController.php)

**Routes principales:**
- `/campaigns` → index() - Liste des campagnes
- `/campaigns/create` → create() - Formulaire création
- `/campaigns/store` → store() - Enregistre nouvelle campagne
- `/campaigns/show/{id}` → show() - Détails campagne
- `/campaigns/edit/{id}` → edit() - Formulaire édition
- `/campaigns/update/{id}` → update() - Met à jour campagne
- `/campaigns/archive/{id}` → archive() - Archive campagne

**Logique de validation (ligne 119-121, 224-226):**
```php
if ($statut === 'en_validation') {
    $this->sendValidationEmails($campaignId);
}
```

**sendValidationEmails() (ligne 390-443):**
1. Récupère la campagne et ses validateurs
2. Récupère les fichiers joints
3. Détermine le mode (validation.mode = passerelle ou direct)
4. Pour chaque validateur:
   - Génère un token unique
   - Construit l'URL: `$baseUrl/validate/$token`
   - Envoie l'email avec MailService

#### MaintenanceController (src/Controllers/MaintenanceController.php)

**Routes:**
- `/maintenance` → index() - Page principale maintenance
- `/maintenance/test-passerelle` → testPasserelle() - Tests complets
- `/maintenance/sync-passerelle` → syncPasserelle() - AJAX sync manuelle

**testPasserelle() (ligne 100-278):**
5 tests intégrés:
1. Configuration (mode, URL, API key)
2. Connectivité à la passerelle (endpoint /health)
3. Récupération des validations en attente
4. Service de synchronisation
5. Vérification base de données (tokens, validations)

Affiche les résultats dans `templates/maintenance/test_passerelle.twig`.

**syncPasserelle() (ligne 280-310):**
- Endpoint AJAX pour synchronisation manuelle
- Retourne JSON
- Utilisé par le bouton "Synchroniser maintenant"

### 4. Système Passerelle

#### Fichiers

**passerelle/config.php**
```php
return [
    'db_path' => __DIR__ . '/validation_queue.db',
    'api_key' => 'CHANGE_ME_IN_PRODUCTION_SECRET_KEY_12345'
];
```

**passerelle/database.php**
```php
function getPasserelleDatabase(): PDO
// Retourne connexion SQLite
// Crée automatiquement la base et les tables si nécessaire
```

**passerelle/validate.php**
Page publique pour les validateurs.

**URL:** `http://localhost/agora/passerelle/validate/{token}`

**Processus:**
1. Extrait token de l'URL
2. Vérifie si déjà utilisé
3. Si POST:
   - Valide action (valide/refuse)
   - Insère dans validation_responses
   - Affiche message succès
4. Sinon affiche formulaire

**passerelle/api.php**
API REST pour communication Agora ↔ Passerelle

**Endpoints:**
```
GET /health
→ Status de l'API

GET /pending-validations
Authorization: Bearer {API_KEY}
→ Liste des validations non synchronisées

POST /sync-completed
Authorization: Bearer {API_KEY}
Body: {"validation_ids": [1, 2, 3]}
→ Marque les validations comme synchronisées

GET /stats
Authorization: Bearer {API_KEY}
→ Statistiques
```

**passerelle/.htaccess**
```apache
RewriteEngine On
RewriteRule ^validate/([a-zA-Z0-9]+)$ validate.php?token=$1 [L,QSA]
RewriteRule ^api\.php/(.*)$ api.php [L,QSA,E=REQUEST_URI:/$1]
```

### 5. Scripts de Synchronisation

#### sync-passerelle.php (racine)

Script principal de synchronisation, compatible CLI et web.

**Utilisation CLI:**
```bash
cd c:/xampp2/htdocs/agora
php sync-passerelle.php
```

**Utilisation Web:**
```
http://localhost/agora/sync-passerelle.php
```

**Sortie:**
```
=== SYNCHRONISATION DE LA PASSERELLE ===
Date: 2025-10-19 23:29:21
Mode de validation: passerelle
URL de la passerelle: http://localhost/agora/passerelle
Clé API: [CONFIGURÉE]

Démarrage de la synchronisation...
--------------------------------------------------

Résultats:
--------------------------------------------------
✓ Synchronisation réussie
  Validations synchronisées: 1
  Message: 1 validation(s) synchronisée(s)

==================================================
Synchronisation terminée
```

**Configuration Cron recommandée:**
```cron
# Toutes les 5 minutes
*/5 * * * * cd /path/to/agora && /usr/bin/php sync-passerelle.php >> logs/sync-passerelle.log 2>&1
```

Voir `docs/CRON_PASSERELLE.md` pour plus de détails.

### 6. Tests et Vérification

#### Scripts de Test Créés

**tests/check_database.php**
- Affiche utilisateurs actifs
- Affiche supports disponibles
- Affiche campagnes (dernières 10)
- Affiche tokens de validation
- Affiche validations

**tests/check_passerelle_db.php**
- Liste tables SQLite
- Affiche validations enregistrées
- Statut de synchronisation

**tests/get_validation_urls.php**
- Liste tous les tokens non utilisés et non expirés
- Affiche les URLs complètes de validation
- Informations sur la campagne et le validateur

**tests/test_validation_form.php**
- Mode interactif ou automatique
- Simule une validation dans la passerelle
- Peut être utilisé pour tests automatisés

**tests/verify_complete_workflow.php**
- Rapport complet sur la campagne ID 100
- 7 sections: campagne, validateurs, tokens, passerelle, Agora, logs, stats
- Vérification end-to-end

**tests/update_passerelle_url.php**
- Met à jour validation_url dans settings
- Utilisé pour corriger la configuration

**tests/fix_validation_keys.php / fix_email_keys.php**
- Scripts de migration pour renommer les clés settings

#### Commandes de Test Rapide

```bash
# Vérifier état complet
php tests/verify_complete_workflow.php

# Voir les URLs de validation disponibles
php tests/get_validation_urls.php

# Simuler une validation
echo "o" | php tests/test_validation_form.php

# Synchroniser
php sync-passerelle.php

# Vérifier résultat
php tests/check_database.php
```

### 7. État Actuel du Projet

#### Campagne de Test en Place

**Campagne ID 100** - "test de campagne"
- Créée le: 2025-10-19 23:22:59
- Statut: en_validation
- 3 validateurs assignés:
  1. Jean Dupont (zidouni@gmail.com) - Token utilisé ✓
  2. Pierre Martin (ogaillard19@gmail.com) - Token disponible
  3. Julie Moreau (ogaillard63@gmail.com) - Token disponible

**Test effectué avec succès:**
- 1 validation soumise via passerelle
- Synchronisation réussie
- Tout le workflow fonctionne

**Tokens disponibles pour tests:**
```
Token 2: 46dba1163fedf70c209ee33dca01d4959976b6b60363963ca01662c2bcdbfbe8
URL: http://localhost/agora/passerelle/validate/46dba1163fedf...

Token 3: 0fbbb231bc6f12955e62a261cff7025ae5abcdd5bf6719c5fabe19df0ff014b7
URL: http://localhost/agora/passerelle/validate/0fbbb231bc6f...
```

#### Configuration Actuelle

**Settings en base de données:**
```
validation_mode = passerelle
validation_url = http://localhost/agora/passerelle
validation_apikey = CHANGE_ME_IN_PRODUCTION_SECRET_KEY_12345
validation_tokendays = 30

email_host = [à configurer]
email_port = 587
email_username = [à configurer]
email_password = [à configurer]
email_encryption = tls
email_fromaddress = noreply@example.com
email_fromname = Agora

app_url = http://localhost/agora/public
app_name = Agora
```

### 8. Problèmes Connus et Solutions

#### Problème 1: Paramètres Mixtes dans Database::update()

**Symptôme:**
```
SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters
```

**Cause:**
```php
// ❌ FAUX
$db->update('table', ['col' => 'val'], 'id = ?', [$id]);
// Génère: SET col = :col WHERE id = ?
// Puis merge [:col => 'val'] + [0 => $id] → erreur paramètres mixtes
```

**Solution:**
```php
// ✅ BON
$db->update('table', ['col' => 'val'], 'id = :id_where', ['id_where' => $id]);
// Génère: SET col = :col WHERE id = :id_where
// Puis merge [:col => 'val', :id_where => $id] → OK
```

#### Problème 2: Multiples Underscores dans settings

**Symptôme:**
```php
config('email.smtp_host') // Retourne VIDE alors que la clé existe
```

**Cause:**
```
En base: email_smtp_host = "smtp.example.com"
ConfigService transforme: email_smtp_host → ['email']['smtp']['host'] (3 niveaux)
Appel config('email.smtp_host') cherche ['email']['smtp_host'] (2 niveaux)
→ Pas de correspondance
```

**Solution:**
```
Renommer les clés:
email_smtp_host → email_host
email_smtp_port → email_port
etc.
```

#### Problème 3: Colonne 'used' inexistante

**Symptôme:**
```
Column not found: 1054 Unknown column 'used' in 'where clause'
```

**Cause:**
La table `validation_tokens` utilise `used_at` (DATETIME), pas `used` (BOOLEAN).

**Solution:**
```php
// ❌ FAUX
WHERE used = 0
UPDATE ... SET used = 1

// ✅ BON
WHERE used_at IS NULL
UPDATE ... SET used_at = NOW()
```

#### Problème 4: PHP 7.3 vs PHP 8.0+

**Syntaxes à éviter:**
```php
// ❌ Union types (PHP 8.0+)
function foo(): array|false {}

// ✅ PHPDoc à la place
/** @return array|false */
function foo() {}

// ❌ Arrow functions (PHP 7.4+)
array_filter($arr, fn($x) => $x > 5)

// ✅ Fonctions anonymes classiques
array_filter($arr, function($x) { return $x > 5; })

// ❌ Null safe operator (PHP 8.0+)
$result = $obj?->method()

// ✅ Vérification explicite
$result = $obj ? $obj->method() : null
```

### 9. Workflows Complets

#### Workflow 1: Créer une Campagne

```
1. Interface web → /campaigns/create
2. Formulaire:
   - Titre, description
   - Demandeur (nom, email)
   - Dates événement
   - Dates campagne
   - Sélection supports (checkboxes)
   - Sélection validateurs (select multiple, ordre)
   - Upload fichiers (multiples)
3. Bouton "Enregistrer comme brouillon" → statut = brouillon
   OU
   Bouton "Envoyer en validation" → statut = en_validation
4. CampaignController::store()
   - Insert campaigns
   - Insert campaign_supports
   - Insert campaign_validators (avec ordre)
   - Upload fichiers → files
   - Log created
   - Si en_validation: sendValidationEmails()
5. Redirect → /campaigns/show/{id}
```

#### Workflow 2: Envoi en Validation

```
1. Campagne statut = en_validation
2. CampaignController::sendValidationEmails()
3. Pour chaque validateur:
   a. ValidationTokenService::generateToken()
      - Génère token SHA256 unique
      - Insert validation_tokens
      - expires_at = NOW() + tokendays
   b. Détermine baseUrl selon mode:
      - passerelle: validation_url
      - direct: app_url
   c. URL = baseUrl + '/validate/' + token
   d. MailService::sendValidationRequest()
      - Email HTML avec lien
      - PJ: fichiers de la campagne
4. Log envoi
```

#### Workflow 3: Validation par Validateur

```
1. Validateur reçoit email
2. Clique sur lien: http://.../passerelle/validate/{token}
3. passerelle/validate.php
   - Extrait token de l'URL
   - Vérifie si déjà utilisé (SELECT ... WHERE token = ?)
4. Si déjà utilisé:
   - Affiche message "Lien déjà utilisé"
   - Affiche action précédente et date
5. Sinon:
   - Affiche formulaire (textarea commentaire, boutons Valider/Refuser)
6. Validateur soumet formulaire (POST)
7. Validation:
   - Vérifie action in ['valide', 'refuse']
   - Insert validation_responses (SQLite)
   - Affiche message succès
```

#### Workflow 4: Synchronisation

```
1. Déclenchement:
   - Cron: */5 * * * *
   - Manuel: bouton dans Maintenance
   - CLI: php sync-passerelle.php
2. PasserelleSyncService::synchronize()
3. Appel API GET /api.php/pending-validations
   - Retourne validations WHERE synced_at IS NULL
4. Pour chaque validation:
   a. Enrichissement:
      - Recherche token dans validation_tokens
      - Récupère campaign_id, user_id
   b. Vérifications:
      - Token existe?
      - Token non utilisé? (used_at IS NULL)
      - Token non expiré? (expires_at > NOW())
   c. Si OK:
      - Insert validations (MySQL)
      - Update validation_tokens SET used_at = NOW()
      - Log campaign_logs (action = 'validated')
      - Vérification statut campagne
5. Notification passerelle:
   - POST /api.php/sync-completed
   - Body: {"validation_ids": [...]}
   - Passerelle: UPDATE SET synced_at = NOW()
6. Retourne résultats (success, synced count, warnings)
```

#### Workflow 5: Vérification Statut Campagne

```
1. Après chaque validation synchronisée
2. PasserelleSyncService::checkCampaignValidationStatus()
3. Requêtes:
   - Total validateurs: COUNT campaign_validators
   - Validations reçues: COUNT validations
   - Refus: COUNT validations WHERE action = 'refuse'
4. Logique:
   - Si refus > 0: statut = 'refusee'
   - Si validations = validateurs: statut = 'validee'
   - Sinon: reste 'en_validation'
5. Update campaigns SET statut = ...
6. Log campaign_logs
```

### 10. Fichiers à Ne Jamais Modifier

Ces fichiers sont critiques et fonctionnent bien:

- ✓ `src/Services/Database.php` - Service de base parfait
- ✓ `src/Helpers/functions.php` - Fonctions helpers (config, url, etc.)
- ✓ `passerelle/database.php` - Création auto de la base SQLite
- ✓ `passerelle/validate.php` - Formulaire de validation testé
- ✓ `passerelle/api.php` - API REST complète
- ✓ `sync-passerelle.php` - Script de sync testé et fonctionnel

### 11. Checklist pour Modifications

Avant toute modification:

- [ ] Vérifier compatibilité PHP 7.3
- [ ] Si nouveau setting: 1 seul underscore maximum
- [ ] Si Database::update(): paramètres nommés dans WHERE
- [ ] Si validation_tokens: utiliser `used_at` pas `used`
- [ ] Tester avec scripts tests/ après modification
- [ ] Vérifier logs d'erreurs
- [ ] Documenter les changements

### 12. Prochaines Étapes Recommandées

#### Configuration Email
```sql
UPDATE settings SET valeur = 'smtp.example.com' WHERE cle = 'email_host';
UPDATE settings SET valeur = 'user@example.com' WHERE cle = 'email_username';
UPDATE settings SET valeur = 'password123' WHERE cle = 'email_password';
```

Puis tester:
```
http://localhost/agora/public/maintenance
→ Cliquer "Test d'envoi d'email"
```

#### Configuration Cron

Éditer crontab:
```bash
crontab -e
```

Ajouter:
```
*/5 * * * * cd /c/xampp2/htdocs/agora && /c/xampp/php/php.exe sync-passerelle.php >> logs/sync-passerelle.log 2>&1
```

#### Tests Complets

1. Créer vraie campagne avec vrais validateurs
2. Vérifier emails reçus
3. Cliquer liens dans emails
4. Valider/refuser
5. Attendre cron (ou sync manuelle)
6. Vérifier statut campagne mis à jour

#### Améliorations Futures

- Notifications aux créateurs de campagne
- Interface pour révoquer tokens
- Logs de synchronisation plus détaillés
- Statistiques de validation par validateur
- Export campagnes en PDF
- Calendrier des campagnes

### 13. Ressources et Documentation

**Fichiers de documentation:**
- `docs/TEST_WORKFLOW_COMPLET.md` - Guide de test détaillé
- `docs/WORKFLOW_TEST_RESULTS.md` - Résultats tests 2025-10-19
- `docs/CRON_PASSERELLE.md` - Configuration cron
- `docs/CONTEXT_FOR_AI.md` - Ce fichier

**URLs importantes:**
- App: http://localhost/agora/public/
- Maintenance: http://localhost/agora/public/maintenance
- Test passerelle: http://localhost/agora/public/maintenance/test-passerelle
- Passerelle: http://localhost/agora/passerelle/
- Validation: http://localhost/agora/passerelle/validate/{token}

**Accès base de données:**
- MySQL: voir `config/database.php`
- SQLite: `passerelle/validation_queue.db`

### 14. Informations de Déploiement

#### Environnement de Développement
- OS: Windows
- Serveur: XAMPP (Apache + MySQL)
- PHP: 7.3
- MySQL: Version XAMPP
- Document root: c:\xampp2\htdocs\

#### Pour Déploiement Production

1. **Sécurité:**
   - Changer validation_apikey
   - Utiliser HTTPS
   - Configurer SMTP sécurisé
   - Vérifier permissions fichiers

2. **Performance:**
   - Activer OPcache PHP
   - Optimiser MySQL
   - Configurer logs

3. **Backup:**
   - Base de données MySQL
   - Base de données SQLite passerelle
   - Fichiers uploads (storage/)

4. **Monitoring:**
   - Logs de synchronisation
   - Logs Apache/PHP
   - Tokens expirés

---

**Version du document:** 1.0
**Dernière mise à jour:** 2025-10-19
**Testé et validé:** Oui ✓
