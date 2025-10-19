# Système de Passerelle (Gateway)

## Vue d'ensemble

Le système de passerelle permet à l'application Agora de fonctionner en deux modes différents selon son environnement d'hébergement :

- **Mode Direct** : Lorsque Agora est hébergée sur Internet et directement accessible
- **Mode Passerelle** : Lorsque Agora est hébergée sur un réseau intranet et a besoin d'une passerelle externe pour les validations

## Problématique

Agora gère des campagnes de communication qui doivent être validées par plusieurs validateurs avant publication. Ces validateurs reçoivent un email avec un lien pour valider ou refuser la campagne.

**Le problème** : Lorsque Agora est hébergée sur un intranet (réseau interne), les validateurs externes ne peuvent pas accéder aux liens de validation car l'application n'est pas accessible depuis Internet.

**La solution** : Une passerelle externe hébergée sur Internet qui :
1. Reçoit les validations des utilisateurs externes
2. Stocke temporairement ces validations dans une base SQLite
3. Expose une API pour qu'Agora puisse récupérer les validations
4. Permet une synchronisation périodique via CRON

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    INTERNET (Public)                        │
│                                                             │
│  ┌──────────────┐                  ┌──────────────────┐   │
│  │  Validateur  │ ─── Email ────> │   Passerelle     │   │
│  └──────────────┘                  │  (Gateway Web)   │   │
│                                     │                  │   │
│                                     │  - validate.php  │   │
│                                     │  - api.php       │   │
│                                     │  - SQLite DB     │   │
│                                     └────────┬─────────┘   │
│                                              │ API          │
└──────────────────────────────────────────────┼─────────────┘
                                               │
                                        CRON (HTTP Pull)
                                               │
┌──────────────────────────────────────────────┼─────────────┐
│                 INTRANET (Privé)             │             │
│                                              ▼             │
│  ┌───────────────────────────────────────────────────┐    │
│  │              AGORA Application                    │    │
│  │                                                   │    │
│  │  - PasserelleSyncService                         │    │
│  │  - bin/sync-validations.php (CRON)               │    │
│  │  - MySQL Database                                │    │
│  └───────────────────────────────────────────────────┘    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Configuration

### Dans Agora (config/app.php)

```php
'validation' => [
    // Mode de fonctionnement : 'direct' ou 'passerelle'
    'mode' => 'passerelle',

    // URL de la passerelle externe (utilisé uniquement en mode passerelle)
    'passerelle_url' => 'https://passerelle-agora.example.com',

    // Clé API pour sécuriser les échanges avec la passerelle
    'passerelle_api_key' => 'CHANGE_ME_IN_PRODUCTION_SECRET_KEY_12345',

    // Durée de validité des tokens (en jours)
    'token_expiry_days' => 30,
]
```

### Dans la Passerelle (passerelle/config.php)

```php
return [
    // Clé API (doit correspondre à celle dans Agora)
    'api_key' => 'CHANGE_ME_IN_PRODUCTION_SECRET_KEY_12345',

    // Chemin vers la base SQLite
    'db_path' => __DIR__ . '/data/validations.db',

    // URL de retour pour les messages de succès
    'app_url' => 'https://passerelle-agora.example.com',
];
```

## Composants du Système

### 1. Passerelle Externe (Dossier `passerelle/`)

#### validate.php
Page web publique accessible aux validateurs pour approuver ou refuser une campagne.

**Fonctionnalités :**
- Affiche les informations de la campagne
- Présente un formulaire de validation (Valider / Refuser)
- Stocke la réponse dans la base SQLite locale
- Marque le token comme utilisé pour éviter les doublons
- Affiche un message de confirmation

**URL d'accès :** `https://passerelle.example.com/validate.php?token=abc123...`

#### api.php
API REST sécurisée par clé API permettant à Agora de récupérer les validations.

**Endpoints disponibles :**

##### GET /api.php?action=pending-validations
Récupère toutes les validations non synchronisées.

**Headers requis :**
```
Authorization: Bearer VOTRE_CLE_API
```

**Réponse :**
```json
{
  "success": true,
  "validations": [
    {
      "id": 1,
      "token": "abc123...",
      "campaign_id": 42,
      "user_id": 5,
      "action": "valide",
      "commentaire": "Approuvé",
      "validated_at": "2025-10-19 14:30:00",
      "synced": 0
    }
  ]
}
```

##### POST /api.php?action=sync-completed
Marque des validations comme synchronisées.

**Headers requis :**
```
Authorization: Bearer VOTRE_CLE_API
Content-Type: application/json
```

**Corps de la requête :**
```json
{
  "ids": [1, 2, 3]
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "3 validation(s) marquée(s) comme synchronisée(s)"
}
```

##### GET /api.php?action=stats
Récupère des statistiques sur les validations.

**Réponse :**
```json
{
  "success": true,
  "stats": {
    "total": 150,
    "synced": 145,
    "pending": 5
  }
}
```

#### database.php
Initialise et gère la base de données SQLite.

**Table `validation_responses` :**
```sql
CREATE TABLE validation_responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token TEXT UNIQUE NOT NULL,           -- Token de validation
    campaign_id INTEGER NOT NULL,         -- ID campagne (pour info)
    user_id INTEGER NOT NULL,             -- ID validateur (pour info)
    action TEXT NOT NULL,                 -- 'valide' ou 'refuse'
    commentaire TEXT,                     -- Commentaire optionnel
    validated_at TEXT NOT NULL,           -- Date/heure de validation
    synced INTEGER DEFAULT 0,             -- 0 = non sync, 1 = synchronisé
    synced_at TEXT,                       -- Date/heure de synchronisation
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
```

### 2. Composants Agora

#### src/Services/PasserelleSyncService.php
Service principal gérant la synchronisation des validations depuis la passerelle.

**Méthodes principales :**

##### synchronize()
Méthode principale appelée par le CRON pour synchroniser toutes les validations.

```php
public function synchronize(): array
```

**Retour :**
```php
[
    'success' => true,
    'synced' => 3,              // Nombre de validations synchronisées
    'errors' => [],             // Tableau des erreurs éventuelles
    'message' => 'Success'
]
```

**Processus :**
1. Vérifie que le mode passerelle est activé
2. Récupère les validations en attente via l'API
3. Enrichit les données avec les IDs Agora
4. Traite chaque validation
5. Confirme la synchronisation à la passerelle

##### fetchPendingValidations()
Appelle l'API de la passerelle pour récupérer les validations non synchronisées.

```php
private function fetchPendingValidations(): ?array
```

##### enrichValidations()
Récupère les campaign_id et user_id depuis les tokens stockés en base Agora.

```php
private function enrichValidations(array $validations): array
```

##### processValidation()
Traite une validation individuelle (mise à jour de la base de données, changement de statut).

```php
private function processValidation(array $validation): void
```

**Actions effectuées :**
1. Marque le token comme utilisé
2. Enregistre la validation dans `campaign_validations`
3. Enregistre un log dans `campaign_logs`
4. Vérifie si tous les validateurs ont répondu
5. Met à jour le statut de la campagne si nécessaire

##### checkCampaignValidationStatus()
Vérifie si tous les validateurs d'une campagne ont répondu et met à jour le statut.

```php
private function checkCampaignValidationStatus(int $campaignId): void
```

**Logique :**
- Si tous ont validé → statut `validee`
- Si au moins un a refusé → statut `refusee`
- Si certains n'ont pas encore répondu → statut reste `en_validation`

##### confirmSync()
Informe la passerelle que les validations ont été synchronisées avec succès.

```php
private function confirmSync(array $ids): void
```

#### bin/sync-validations.php
Script CRON qui exécute la synchronisation périodiquement.

**Utilisation :**

**Linux/Mac (crontab) :**
```bash
# Toutes les 5 minutes
*/5 * * * * php /path/to/agora/bin/sync-validations.php >> /var/log/agora-sync.log 2>&1
```

**Windows (Planificateur de tâches) :**
```
Programme : C:\xampp2\php\php.exe
Arguments : "C:\xampp2\htdocs\agora\bin\sync-validations.php"
Déclencheur : Toutes les 5 minutes
```

**Sortie du script :**
```
[2025-10-19 14:35:00] [INFO] Démarrage de la synchronisation des validations
[2025-10-19 14:35:01] [SUCCESS] Synchronisation réussie: 3 validation(s) synchronisée(s)
[2025-10-19 14:35:01] [INFO] Synchronisation terminée
```

**Codes de sortie :**
- `0` : Succès
- `1` : Erreur (pour monitoring CRON)

#### src/Services/CampaignLogService.php
Gère les logs d'événements des campagnes.

**Nouvelle méthode pour la passerelle :**

```php
public function logSynced(int $campaignId, int $userId, string $validatorName, string $action): void
```

Cette méthode enregistre spécifiquement les validations synchronisées depuis la passerelle avec la mention "(synchronisé depuis la passerelle)" pour traçabilité.

#### src/Controllers/CampaignController.php
Adapte la génération des liens de validation selon le mode configuré.

**Méthode `sendValidationEmails()` (lignes 425-442) :**

```php
// Déterminer l'URL de base selon le mode
$validationMode = $appConfig['validation']['mode'] ?? 'direct';

if ($validationMode === 'passerelle') {
    $baseUrl = $appConfig['validation']['passerelle_url'];
} else {
    $baseUrl = $appConfig['url'];
}

$validationUrl = $baseUrl . '/validate/' . $token;
```

## Flux de Données Complet

### Mode Passerelle Activé

#### 1. Envoi de la Campagne en Validation

```
┌─────────────┐
│   Agora     │
│ Controller  │
└──────┬──────┘
       │
       │ 1. Génère des tokens uniques
       │    pour chaque validateur
       │
       ▼
┌──────────────────┐
│  validation_     │
│  tokens (MySQL)  │  Token: abc123...
└──────┬───────────┘  Campaign ID: 42
       │              User ID: 5 (validateur)
       │              Expires: 2025-11-19
       │
       │ 2. Construit l'URL avec passerelle_url
       │
       ▼
┌──────────────────────────────────────┐
│  Email au Validateur                 │
│                                      │
│  Lien: https://passerelle.com/       │
│        validate.php?token=abc123...  │
└──────────────────────────────────────┘
```

#### 2. Validation par l'Utilisateur Externe

```
┌─────────────┐
│ Validateur  │
│ (Internet)  │
└──────┬──────┘
       │
       │ 3. Clique sur le lien
       │
       ▼
┌────────────────────────┐
│  Passerelle            │
│  validate.php          │
│                        │
│  - Affiche formulaire  │
│  - Reçoit la réponse   │
└───────────┬────────────┘
            │
            │ 4. Stocke dans SQLite
            │
            ▼
┌─────────────────────────────┐
│  validation_responses       │
│  (SQLite)                   │
│                             │
│  - token: abc123...         │
│  - action: 'valide'         │
│  - commentaire: "OK"        │
│  - synced: 0                │
└─────────────────────────────┘
```

#### 3. Synchronisation Périodique (CRON)

```
┌────────────────────────┐
│  CRON (toutes les      │
│  5 minutes)            │
└──────────┬─────────────┘
           │
           │ 5. Lance le script
           │
           ▼
┌───────────────────────────┐
│  bin/sync-validations.php │
└──────────┬────────────────┘
           │
           │ 6. Appelle
           │
           ▼
┌──────────────────────────────┐
│  PasserelleSyncService       │
│                              │
│  synchronize()               │
└──────┬───────────────────────┘
       │
       │ 7. GET /api.php?action=pending-validations
       │    Header: Authorization: Bearer KEY
       │
       ▼
┌─────────────────────────────┐
│  Passerelle API             │
│                             │
│  Retourne validations       │
│  avec synced = 0            │
└──────────┬──────────────────┘
           │
           │ 8. Retour JSON
           │
           ▼
┌───────────────────────────────┐
│  PasserelleSyncService        │
│                               │
│  enrichValidations()          │
│  - Récupère campaign_id       │
│  - Récupère user_id           │
│    depuis validation_tokens   │
└──────────┬────────────────────┘
           │
           │ 9. Pour chaque validation
           │
           ▼
┌───────────────────────────────┐
│  processValidation()          │
│                               │
│  - Marque token utilisé       │
│  - Insert campaign_validations│
│  - Insert campaign_logs       │
│  - Vérifie statut campagne    │
└──────────┬────────────────────┘
           │
           │ 10. POST /api.php?action=sync-completed
           │     Body: {"ids": [1, 2, 3]}
           │
           ▼
┌─────────────────────────────┐
│  Passerelle API             │
│                             │
│  UPDATE synced = 1          │
│  WHERE id IN (...)          │
└─────────────────────────────┘
```

### Mode Direct Activé

En mode direct, le processus est simplifié :

```
┌─────────────┐
│   Agora     │
│ Controller  │
└──────┬──────┘
       │
       │ 1. Génère token
       │ 2. URL directe: https://agora.com/validate/abc123...
       │
       ▼
┌──────────────┐
│  Validateur  │
└──────┬───────┘
       │
       │ 3. Valide directement sur Agora
       │
       ▼
┌────────────────────────┐
│  Agora                 │
│  ValidationController  │
│                        │
│  - Traitement immédiat │
│  - Pas de sync         │
└────────────────────────┘
```

## Base de Données

### Tables Agora (MySQL)

#### validation_tokens
Stocke les tokens générés pour les validateurs.

```sql
CREATE TABLE validation_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT 0,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### campaign_validations
Enregistre les réponses de validation.

```sql
CREATE TABLE campaign_validations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    validation_status ENUM('valide', 'refuse') NOT NULL,
    commentaire TEXT,
    validated_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### campaign_logs
Journal d'événements des campagnes (inclut les validations synchronisées).

```sql
CREATE TABLE campaign_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    old_values JSON,
    new_values JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Table Passerelle (SQLite)

#### validation_responses
Queue temporaire des validations en attente de synchronisation.

```sql
CREATE TABLE validation_responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token TEXT UNIQUE NOT NULL,
    campaign_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    action TEXT CHECK(action IN ('valide', 'refuse')) NOT NULL,
    commentaire TEXT,
    validated_at TEXT NOT NULL,
    synced INTEGER DEFAULT 0,
    synced_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
```

## Sécurité

### 1. Authentification API
Toutes les requêtes vers l'API de la passerelle doivent inclure un header d'autorisation :

```
Authorization: Bearer VOTRE_CLE_API_SECRETE
```

La clé doit être identique dans `config/app.php` (Agora) et `passerelle/config.php`.

### 2. Tokens à Usage Unique
Chaque token de validation :
- Est unique et aléatoire (64 caractères)
- Expire après X jours (configurable)
- Ne peut être utilisé qu'une seule fois
- Est marqué comme `used = 1` après utilisation

### 3. Validation des Données
- Les actions sont limitées à `'valide'` ou `'refuse'` via CHECK constraint
- Les IDs de campagne et utilisateur sont validés lors de l'enrichissement
- Les tokens expirés sont rejetés

### 4. HTTPS Obligatoire
La passerelle **DOIT** être accessible en HTTPS pour protéger les tokens en transit.

## Installation et Déploiement

### Déploiement de la Passerelle

#### Prérequis
- Serveur web (Apache/Nginx) accessible depuis Internet
- PHP 7.4+
- Extension PHP SQLite3 activée
- Certificat SSL valide (Let's Encrypt recommandé)

#### Étapes

1. **Créer le dossier sur le serveur**
```bash
mkdir -p /var/www/passerelle-agora
cd /var/www/passerelle-agora
```

2. **Copier les fichiers**
```bash
# Copier tous les fichiers du dossier passerelle/
scp -r passerelle/* user@server:/var/www/passerelle-agora/
```

3. **Créer le dossier data et définir les permissions**
```bash
mkdir data
chmod 755 data
touch data/validations.db
chmod 664 data/validations.db
chown www-data:www-data data data/validations.db
```

4. **Configurer config.php**
```php
return [
    'api_key' => 'GENERER_UNE_CLE_SECRETE_ICI',
    'db_path' => __DIR__ . '/data/validations.db',
    'app_url' => 'https://passerelle-agora.votredomaine.com',
];
```

5. **Configurer le serveur web**

**Apache (.htaccess) :**
```apache
RewriteEngine On

# Route pour validation
RewriteRule ^validate/(.+)$ validate.php?token=$1 [L,QSA]

# Protection du dossier data
<Directory "data">
    Require all denied
</Directory>
```

**Nginx :**
```nginx
server {
    listen 443 ssl;
    server_name passerelle-agora.votredomaine.com;
    root /var/www/passerelle-agora;

    ssl_certificate /etc/letsencrypt/live/passerelle-agora.votredomaine.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/passerelle-agora.votredomaine.com/privkey.pem;

    location /validate/ {
        rewrite ^/validate/(.+)$ /validate.php?token=$1 last;
    }

    location /data {
        deny all;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

6. **Tester l'installation**
```bash
# Vérifier que la base SQLite est créée
php database.php

# Tester l'API
curl -H "Authorization: Bearer VOTRE_CLE_API" \
     https://passerelle-agora.votredomaine.com/api.php?action=stats
```

### Configuration d'Agora

1. **Modifier config/app.php**
```php
'validation' => [
    'mode' => 'passerelle',
    'passerelle_url' => 'https://passerelle-agora.votredomaine.com',
    'passerelle_api_key' => 'MEME_CLE_QUE_PASSERELLE',
    'token_expiry_days' => 30,
]
```

2. **Configurer le CRON**

**Linux (crontab -e) :**
```bash
# Synchronisation toutes les 5 minutes
*/5 * * * * cd /var/www/agora && php bin/sync-validations.php >> /var/log/agora-sync.log 2>&1
```

**Windows (Planificateur de tâches) :**
- Créer une nouvelle tâche
- Déclencheur : Répétition toutes les 5 minutes
- Action : Démarrer un programme
  - Programme : `C:\xampp2\php\php.exe`
  - Arguments : `"C:\xampp2\htdocs\agora\bin\sync-validations.php"`
  - Répertoire : `C:\xampp2\htdocs\agora`

3. **Tester la synchronisation manuelle**
```bash
php bin/sync-validations.php
```

## Monitoring et Maintenance

### Logs de Synchronisation

Les logs CRON contiennent toutes les informations de synchronisation :

```
[2025-10-19 14:35:00] [INFO] Démarrage de la synchronisation des validations
[2025-10-19 14:35:01] [SUCCESS] Synchronisation réussie: 3 validation(s) synchronisée(s)
[2025-10-19 14:35:01] [INFO] Synchronisation terminée
```

En cas d'erreur :
```
[2025-10-19 14:40:00] [INFO] Démarrage de la synchronisation des validations
[2025-10-19 14:40:01] [ERROR] Échec de la synchronisation: HTTP 401 - Unauthorized
[2025-10-19 14:40:01] [FATAL] Erreur fatale: API authentication failed
```

### Vérification de la Passerelle

**Obtenir les statistiques :**
```bash
curl -H "Authorization: Bearer VOTRE_CLE_API" \
     https://passerelle-agora.votredomaine.com/api.php?action=stats
```

**Résultat :**
```json
{
  "success": true,
  "stats": {
    "total": 150,
    "synced": 145,
    "pending": 5
  }
}
```

Si `pending` augmente sans diminuer, cela indique un problème de synchronisation.

### Journal d'Événements Agora

Accéder à **Maintenance > Journal d'événements** pour voir :
- Toutes les validations synchronisées (mention "synchronisé depuis la passerelle")
- Les erreurs éventuelles
- L'historique complet des opérations

### Nettoyage de la Base SQLite

La table `validation_responses` peut grossir avec le temps. Script de nettoyage recommandé :

```php
// passerelle/cleanup.php
<?php
require_once __DIR__ . '/database.php';

$db = getDatabase();

// Supprimer les validations synchronisées de plus de 90 jours
$stmt = $db->prepare("
    DELETE FROM validation_responses
    WHERE synced = 1
    AND synced_at < datetime('now', '-90 days')
");
$stmt->execute();

echo "Nettoyage effectué: " . $db->changes() . " enregistrement(s) supprimé(s)\n";
```

Ajouter au CRON (1x par semaine) :
```bash
0 2 * * 0 php /var/www/passerelle-agora/cleanup.php
```

## Dépannage

### Problème : Les validations ne sont pas synchronisées

**Vérifications :**

1. **Le CRON s'exécute-t-il ?**
```bash
# Linux
grep sync-validations /var/log/syslog

# Windows : vérifier l'historique du Planificateur de tâches
```

2. **L'API est-elle accessible ?**
```bash
curl -v -H "Authorization: Bearer VOTRE_CLE" \
     https://passerelle.com/api.php?action=stats
```

3. **Les clés API correspondent-elles ?**
- Vérifier `config/app.php` (Agora)
- Vérifier `passerelle/config.php`

4. **Des validations sont-elles en attente ?**
```bash
sqlite3 /var/www/passerelle-agora/data/validations.db \
        "SELECT COUNT(*) FROM validation_responses WHERE synced = 0;"
```

5. **Logs du script de synchronisation :**
```bash
tail -f /var/log/agora-sync.log
```

### Problème : Erreur 401 Unauthorized

**Cause :** Clé API incorrecte ou manquante

**Solution :**
- Vérifier que les clés API sont identiques dans Agora et la passerelle
- Vérifier le header `Authorization: Bearer VOTRE_CLE`
- Vérifier que la clé ne contient pas d'espaces ou caractères spéciaux

### Problème : Token invalide ou expiré

**Cause :** Le token a expiré ou a déjà été utilisé

**Vérification :**
```sql
-- Dans Agora (MySQL)
SELECT * FROM validation_tokens WHERE token = 'abc123...';
```

**Solution :**
- Vérifier `expires_at` (doit être dans le futur)
- Vérifier `used` (doit être 0)
- Régénérer le token si nécessaire

### Problème : La passerelle ne stocke pas les validations

**Vérifications :**

1. **Permissions sur la base SQLite :**
```bash
ls -la /var/www/passerelle-agora/data/validations.db
# Doit être lisible/écrivable par www-data
```

2. **Extension SQLite activée dans PHP :**
```bash
php -m | grep sqlite
# Doit afficher "sqlite3"
```

3. **Logs d'erreur PHP :**
```bash
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/nginx/error.log
```

## Basculement entre les Modes

### Passage en Mode Direct (Agora hébergée sur Internet)

1. **Modifier la configuration**
```php
// config/app.php
'validation' => [
    'mode' => 'direct',  // Changé de 'passerelle' à 'direct'
    // ...
]
```

2. **Désactiver le CRON de synchronisation**
```bash
# Linux : commenter la ligne dans crontab
# */5 * * * * php /var/www/agora/bin/sync-validations.php

# Windows : désactiver la tâche planifiée
```

3. **Les nouvelles campagnes utiliseront des liens directs**
- Les emails contiendront : `https://agora.votredomaine.com/validate/token`
- Pas besoin de passerelle
- Validation immédiate, sans synchronisation

4. **Les campagnes en cours**
- Les tokens déjà envoyés avec l'ancienne URL de passerelle fonctionneront toujours
- Seules les nouvelles campagnes utiliseront le mode direct

### Passage en Mode Passerelle (Agora hébergée sur Intranet)

1. **Déployer la passerelle** (voir section Installation)

2. **Modifier la configuration**
```php
// config/app.php
'validation' => [
    'mode' => 'passerelle',
    'passerelle_url' => 'https://passerelle-agora.votredomaine.com',
    'passerelle_api_key' => 'VOTRE_CLE',
    'token_expiry_days' => 30,
]
```

3. **Activer le CRON de synchronisation**

4. **Tester**
- Créer une campagne de test
- Vérifier que l'email contient l'URL de la passerelle
- Valider via la passerelle
- Attendre la synchronisation CRON (max 5 min)
- Vérifier dans Agora que la validation est enregistrée

## Améliorations Futures Possibles

### 1. Interface d'Administration pour la Passerelle
- Dashboard des validations en attente
- Statistiques graphiques
- Logs détaillés
- Gestion manuelle de la queue

### 2. Webhooks
- Notification immédiate vers Agora lors d'une validation
- Réduction du délai de synchronisation (de 5 min à quelques secondes)

### 3. Authentification Renforcée
- JWT tokens
- OAuth2
- Rotation automatique des clés API

### 4. Monitoring Avancé
- Health checks automatiques
- Alertes email si synchronisation échoue
- Métriques Prometheus/Grafana

### 5. Haute Disponibilité
- Plusieurs passerelles en load balancing
- Base de données partagée (PostgreSQL au lieu de SQLite)
- Cache Redis pour les validations

## Références

### Fichiers Clés

**Agora :**
- [config/app.php](../config/app.php) - Configuration du mode de validation
- [src/Services/PasserelleSyncService.php](../src/Services/PasserelleSyncService.php) - Service de synchronisation
- [src/Services/CampaignLogService.php](../src/Services/CampaignLogService.php) - Logs d'événements
- [src/Controllers/CampaignController.php](../src/Controllers/CampaignController.php) - Génération des liens
- [bin/sync-validations.php](../bin/sync-validations.php) - Script CRON

**Passerelle :**
- [passerelle/config.php](../passerelle/config.php) - Configuration
- [passerelle/database.php](../passerelle/database.php) - Gestion SQLite
- [passerelle/validate.php](../passerelle/validate.php) - Page de validation
- [passerelle/api.php](../passerelle/api.php) - API REST

### Ressources Externes
- [Documentation SQLite](https://www.sqlite.org/docs.html)
- [cURL PHP](https://www.php.net/manual/fr/book.curl.php)
- [Gestion des CRON sous Linux](https://crontab.guru/)
