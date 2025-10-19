# Système de Validation Publique

## Vue d'ensemble

Le système de validation publique permet aux validateurs d'accéder aux campagnes et de les valider **depuis l'extérieur de l'intranet**, via un lien sécurisé envoyé par email.

## Fonctionnalités

### 1. 📎 Pièces jointes dans l'email

Lorsqu'une campagne est envoyée en validation, les **visuels sont automatiquement joints à l'email** (max 5 fichiers de 5 Mo chacun):
- Images (JPEG, PNG, GIF, etc.)
- PDFs

**Limite:** Maximum 5 fichiers pour éviter que l'email soit trop lourd.

### 2. 🔗 Lien de validation public sécurisé

Chaque validateur reçoit un **lien unique et sécurisé** qui fonctionne:
- ✅ Depuis l'intranet
- ✅ Depuis l'extérieur (Internet)
- ✅ Sur mobile, tablette, ordinateur
- ✅ Sans besoin de connexion à l'application

**Format du lien:**
```
http://votre-domaine.com/agora/public/validate/abcd1234...xyz
```

### 3. 🔐 Sécurité

- **Token unique** de 64 caractères pour chaque validateur
- **Expiration** après 30 jours (configurable)
- Token **à usage unique** (marqué comme utilisé après validation)
- Traçabilité (IP et User-Agent enregistrés)

### 4. ✅ Page de validation

La page publique affiche:
- Toutes les informations de la campagne
- Les visuels (avec possibilité de téléchargement)
- Les supports de diffusion
- Le circuit de validation et son état
- Un formulaire pour valider ou refuser avec commentaire

## Architecture

### Tables créées

#### `validation_tokens`
```sql
- id (INT UNSIGNED)
- campaign_id (INT UNSIGNED)
- user_id (INT UNSIGNED)
- token (VARCHAR(64)) -- Token unique
- expires_at (DATETIME)
- used_at (DATETIME NULL)
- ip_address (VARCHAR(45))
- user_agent (TEXT)
- created_at (TIMESTAMP)
```

### Services créés

#### `ValidationTokenService`
Gère la création, validation et révocation des tokens:
- `generateToken()` - Crée un token unique
- `validateToken()` - Vérifie qu'un token est valide
- `markAsUsed()` - Marque un token comme utilisé
- `cleanExpiredTokens()` - Nettoie les tokens expirés (cron)
- `revokeTokensByCampaign()` - Révoque tous les tokens d'une campagne

### Contrôleurs créés

#### `PublicValidationController`
Gère l'accès public à la validation:
- `show()` - Affiche la page de validation
- `submit()` - Traite la soumission (valider/refuser)

### Templates créés

#### `templates/public/validation.twig`
Page publique responsive avec:
- Affichage de la campagne
- Circuit de validation
- Formulaire de validation/refus
- Design adapté mobile

## Workflow

### 1. Création/Modification de campagne

```
Utilisateur crée une campagne
    ↓
Campagne passe en statut "en_validation"
    ↓
Pour chaque validateur:
    ├─ Génération d'un token unique
    ├─ Création du lien public
    ├─ Envoi email avec:
    │   ├─ Visuels en pièces jointes
    │   └─ Lien de validation public
```

### 2. Validation depuis l'extérieur

```
Validateur clique sur le lien dans l'email
    ↓
Vérification du token
    ↓
Si valide:
    ├─ Affichage de la page de validation
    ├─ Validateur consulte la campagne
    ├─ Validateur clique "Valider" ou "Refuser"
    ├─ Enregistrement dans la table `validations`
    ├─ Token marqué comme "utilisé"
    └─ Mise à jour du statut si refusé
```

## Configuration requise

### Accès depuis l'extérieur

Pour que les liens fonctionnent depuis Internet, votre serveur doit être:
1. **Accessible publiquement** via une URL (ex: `https://agora.votre-domaine.com`)
2. Configuré dans `config/app.php`:
   ```php
   'url' => 'https://agora.votre-domaine.com/agora/public',
   ```

### Emails

Les identifiants SMTP doivent être configurés dans `config/mail.php` pour envoyer les emails avec pièces jointes.

## Limites et contraintes

### Taille des pièces jointes
- **Max 5 fichiers** par email
- **Max 5 Mo par fichier**
- Seuls les images et PDFs sont joints

> 💡 **Recommandation:** Si les fichiers sont trop lourds, ils ne seront pas joints, mais restent accessibles via le lien public.

### Durée de validité
- Les tokens expirent après **30 jours** par défaut
- Configurable dans `CampaignController->sendValidationEmails()`

### Utilisation unique
- Une fois qu'un validateur a validé/refusé, le token est marqué comme utilisé
- Il peut toujours consulter la page mais ne peut plus re-valider

## Maintenance

### Nettoyage des tokens expirés

Créer un cron qui exécute quotidiennement:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dbConfig = require __DIR__ . '/../config/database.php';
$database = new Agora\Services\Database($dbConfig);
$tokenService = new Agora\Services\ValidationTokenService($database);

// Supprimer les tokens expirés depuis plus de 90 jours
$deleted = $tokenService->cleanExpiredTokens(90);
echo "$deleted tokens supprimés\n";
```

### Révocation manuelle

Pour révoquer tous les tokens d'une campagne (si refaite):

```php
$tokenService->revokeTokensByCampaign($campaignId);
```

## Exemples d'utilisation

### Email reçu par le validateur

```
Sujet: Demande de validation - Campagne Fête de la Musique 2025

Bonjour Jean Dupont,

Une nouvelle campagne de communication nécessite votre validation :

Campagne Fête de la Musique 2025

📎 3 visuels joints à cet email

[Bouton: Consulter et valider en ligne]

💡 Accès depuis l'extérieur : Ce lien est accessible depuis n'importe où,
même en dehors de l'intranet.
```

### Page de validation

Le validateur voit:
- Titre et description de la campagne
- Informations complètes (demandeur, dates, supports)
- Visuels avec téléchargement
- Circuit de validation avec les autres validateurs
- Formulaire simple: Commentaire + Bouton "Valider" ou "Refuser"

## Sécurité et confidentialité

### Bonnes pratiques
- ✅ Les tokens sont générés avec `random_bytes()` (cryptographiquement sécurisés)
- ✅ Les tokens sont uniques et imprévisibles (64 caractères hexadécimaux)
- ✅ Expiration automatique après 30 jours
- ✅ Traçabilité: IP et User-Agent enregistrés
- ✅ Usage unique: impossible de valider deux fois

### Points d'attention
- ⚠️ Le lien est public et non protégé par mot de passe
- ⚠️ Toute personne ayant le lien peut voir la campagne
- ⚠️ Ne pas partager le lien de validation
- ⚠️ Pour des campagnes très sensibles, privilégier la validation interne uniquement

## Support

En cas de problème:
1. Vérifier que le serveur est accessible depuis l'extérieur
2. Vérifier la configuration de l'URL dans `config/app.php`
3. Vérifier les logs de validation dans la table `validation_tokens`
4. Vérifier que le token n'est pas expiré (`expires_at`)
