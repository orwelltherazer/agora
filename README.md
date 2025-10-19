# AGORA - Système de Gestion de Campagnes de Communication

![PHP Version](https://img.shields.io/badge/PHP-7.3%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![License](https://img.shields.io/badge/license-Proprietary-red)
![Version](https://img.shields.io/badge/version-1.0-green)
![Status](https://img.shields.io/badge/status-Production%20Ready-brightgreen)

Application web de gestion de campagnes de communication pour collectivités locales, avec système de validation externe par passerelle.

## Caractéristiques

- 📝 **Gestion de campagnes** - Création, édition, validation, publication de campagnes de communication
- 👥 **Workflow de validation** - Processus de validation multi-validateurs avec ordre configurable
- 🔐 **Validation externe (Passerelle)** - Système permettant aux validateurs de valider depuis l'extérieur de l'intranet
- 📧 **Notifications email** - Envoi automatique d'emails aux validateurs avec liens uniques
- 📎 **Gestion de fichiers** - Upload et versioning de visuels et documents
- 📊 **Tableau de bord** - Vue d'ensemble des campagnes en cours, validées, publiées
- 🔧 **Interface de maintenance** - Tests et diagnostics pour administrateurs
- 📅 **Planification** - Gestion des dates d'événements et de diffusion
- 🎨 **Multi-supports** - Facebook, Intranet, Site web, Panneaux, Prospectus, etc.

## Prérequis

- PHP >= 7.3
- MySQL >= 5.7
- SQLite3 (pour la passerelle)
- Composer
- Serveur web (Apache avec mod_rewrite)

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/orwelltherazer/agora.git
cd agora
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration de la base de données

Copiez le fichier de configuration exemple:

```bash
cp config/database.php.example config/database.php
```

Éditez `config/database.php` avec vos paramètres:

```php
return [
    'host' => 'localhost',
    'database' => 'agora',
    'username' => 'votre_utilisateur',
    'password' => 'votre_mot_de_passe',
    'charset' => 'utf8mb4',
];
```

### 4. Créer la base de données

Importez le schéma de base de données:

```bash
mysql -u root -p agora < database/schema.sql
```

Importez les données de base (supports, utilisateurs de test):

```bash
mysql -u root -p agora < database/seeds.sql
```

### 5. Configuration Apache

Assurez-vous que le `DocumentRoot` pointe vers le dossier `public/`:

```apache
<VirtualHost *:80>
    ServerName agora.local
    DocumentRoot "/path/to/agora/public"

    <Directory "/path/to/agora/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. Permissions

```bash
chmod -R 755 storage/
chmod -R 755 logs/
chmod -R 755 passerelle/
```

## Configuration

### Paramètres Système

Les paramètres sont stockés dans la table `settings` de la base de données.

**Configuration Email:**

```sql
UPDATE settings SET valeur = 'smtp.example.com' WHERE cle = 'email_host';
UPDATE settings SET valeur = '587' WHERE cle = 'email_port';
UPDATE settings SET valeur = 'user@example.com' WHERE cle = 'email_username';
UPDATE settings SET valeur = 'mot_de_passe' WHERE cle = 'email_password';
UPDATE settings SET valeur = 'tls' WHERE cle = 'email_encryption';
UPDATE settings SET valeur = 'noreply@example.com' WHERE cle = 'email_fromaddress';
UPDATE settings SET valeur = 'Agora' WHERE cle = 'email_fromname';
```

**Configuration Passerelle:**

```sql
UPDATE settings SET valeur = 'passerelle' WHERE cle = 'validation_mode';
UPDATE settings SET valeur = 'https://votre-passerelle.com' WHERE cle = 'validation_url';
UPDATE settings SET valeur = 'votre_cle_api_secrete' WHERE cle = 'validation_apikey';
UPDATE settings SET valeur = '30' WHERE cle = 'validation_tokendays';
```

### Synchronisation Passerelle (Cron)

Pour synchroniser automatiquement les validations depuis la passerelle:

```bash
crontab -e
```

Ajoutez:

```cron
*/5 * * * * cd /path/to/agora && /usr/bin/php sync-passerelle.php >> logs/sync-passerelle.log 2>&1
```

Voir `docs/CRON_PASSERELLE.md` pour plus de détails.

## Utilisation

### Accès à l'application

- **Application principale:** `http://votre-serveur/agora/public/`
- **Interface maintenance:** `http://votre-serveur/agora/public/maintenance`
- **Passerelle de validation:** `http://votre-serveur/agora/passerelle/`

### Comptes par défaut

Après import des seeds:

- **Admin:** admin@agora.local / admin123
- **Utilisateur:** user@agora.local / user123

⚠️ **Changez ces mots de passe en production!**

### Workflow de Validation

1. **Créer une campagne** - Remplir le formulaire avec titre, dates, supports, etc.
2. **Assigner des validateurs** - Sélectionner les utilisateurs qui doivent valider (avec ordre)
3. **Envoyer en validation** - Cliquer sur "Envoyer en validation"
4. **Emails envoyés** - Chaque validateur reçoit un email avec un lien unique
5. **Validation externe** - Les validateurs cliquent le lien et valident/refusent
6. **Synchronisation** - Le cron synchronise les validations vers Agora
7. **Statut mis à jour** - La campagne passe à "validée" ou "refusée"

## Tests

### Scripts de Test

Le projet inclut plusieurs scripts de test dans `tests/`:

```bash
# Vérifier l'état de la base de données
php tests/check_database.php

# Vérifier la passerelle
php tests/check_passerelle_db.php

# Obtenir les URLs de validation disponibles
php tests/get_validation_urls.php

# Tester le workflow complet
php tests/verify_complete_workflow.php

# Synchroniser manuellement
php sync-passerelle.php
```

### Interface de Test

Accédez à `/maintenance/test-passerelle` pour:
- Vérifier la configuration
- Tester la connectivité
- Voir les validations en attente
- Synchroniser manuellement

## Architecture

### Structure du Projet

```
agora/
├── config/              # Configuration (database, app)
├── database/           # Schémas et migrations SQL
├── docs/               # Documentation complète
├── logs/               # Logs de l'application
├── passerelle/         # Système de validation externe (SQLite)
├── public/             # Point d'entrée web
├── src/
│   ├── Controllers/    # Contrôleurs MVC
│   ├── Helpers/        # Fonctions helpers
│   ├── Middleware/     # Authentification, etc.
│   ├── Models/         # Modèles de données
│   ├── Repositories/   # Requêtes complexes
│   └── Services/       # Services métier
├── storage/
│   └── uploads/        # Fichiers uploadés
├── templates/          # Vues Twig
├── tests/              # Scripts de test
└── vendor/             # Dépendances Composer
```

### Technologies

- **Backend:** PHP 7.3+, Architecture MVC
- **Base de données:** MySQL (principale), SQLite (passerelle)
- **Templates:** Twig 3.x
- **Frontend:** Tailwind CSS, Alpine.js
- **Email:** PHPMailer
- **Sécurité:** Sessions PHP, tokens de validation SHA256

## Système de Passerelle

La passerelle est un système autonome permettant la validation depuis l'extérieur:

- **Base de données:** SQLite indépendante
- **API REST:** Endpoints pour synchronisation
- **Interface publique:** Formulaire de validation accessible sans authentification
- **Sécurité:** Tokens uniques à usage unique avec expiration

Voir `docs/TEST_WORKFLOW_COMPLET.md` pour le guide complet.

## Documentation

- 📖 [Guide de Test Complet](docs/TEST_WORKFLOW_COMPLET.md)
- 📊 [Résultats des Tests](docs/WORKFLOW_TEST_RESULTS.md)
- ⏰ [Configuration Cron](docs/CRON_PASSERELLE.md)
- 🤖 [Contexte pour IA](docs/CONTEXT_FOR_AI.md)

## Problèmes Connus

### Paramètres Multiples Underscores

Le système de configuration transforme les underscores en hiérarchie. Évitez les clés avec plusieurs underscores:

❌ `email_smtp_host` (3 niveaux)
✅ `email_host` (2 niveaux)

### Compatibilité PHP

Le code est compatible PHP 7.3. N'utilisez pas:
- Union types (`array|false`)
- Arrow functions (`fn() =>`)
- Null safe operator (`?->`)

Voir `docs/CONTEXT_FOR_AI.md` section 8 pour tous les problèmes connus.

## Contribution

1. Fork le projet
2. Créez votre branche (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

### Checklist avant Commit

- [ ] Code compatible PHP 7.3
- [ ] Tests passent (`php tests/verify_complete_workflow.php`)
- [ ] Settings avec 1 seul underscore maximum
- [ ] `Database::update()` avec paramètres nommés
- [ ] Documentation mise à jour si nécessaire

## Licence

Ce projet est sous licence propriétaire. Tous droits réservés.

## Support

Pour toute question ou problème:
- Ouvrir une issue sur GitHub
- Consulter la documentation dans `docs/`

## Statut du Projet

✅ **Version 1.0** - Système de validation par passerelle fonctionnel et testé

**Testé et validé le:** 2025-10-19

**Prochaines fonctionnalités:**
- Notifications temps réel
- Export PDF des campagnes
- Tableau de bord statistiques
- API REST pour intégrations externes
