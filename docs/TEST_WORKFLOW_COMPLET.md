# Test du Workflow Complet de Validation par Passerelle

## Vue d'ensemble

Ce guide vous permet de tester le processus complet de validation d'une campagne via la passerelle externe.

## État Actuel

Vous avez déjà une campagne de test (ID 100: "test de campagne") avec 3 tokens de validation non utilisés :

1. **Dupont Jean** (zidouni@gmail.com)
2. **Martin Pierre** (ogaillard19@gmail.com)
3. **Moreau Julie** (ogaillard63@gmail.com)

## Workflow Complet

```
[Agora] Création campagne → Envoi emails
         ↓
[Email] Validateur reçoit email avec URL unique
         ↓
[Passerelle] Validateur clique sur l'URL → Formulaire de validation
         ↓
[Passerelle DB] Validation enregistrée (SQLite)
         ↓
[Synchronisation] Cron ou manuel
         ↓
[Agora DB] Validation importée (MySQL)
         ↓
[Agora] Statut campagne mis à jour
```

## Étapes du Test

### Étape 1 : Obtenir les URLs de validation

Exécutez ce script pour voir les URLs de test :

```bash
cd c:/xampp2/htdocs/agora
php tests/get_validation_urls.php
```

Vous obtiendrez des URLs comme :
```
http://localhost/agora/passerelle/validate/a3f77766157428231a7bd6032be338be042ab29f0ed5ef77342133beb4d46dd5
```

### Étape 2 : Tester la validation

1. **Copiez une des URLs** depuis le résultat de l'étape 1

2. **Ouvrez l'URL dans un navigateur**
   - Vous devriez voir le formulaire de validation de la passerelle
   - Design moderne avec Tailwind CSS
   - 2 boutons : "Valider" (vert) et "Refuser" (rouge)

3. **Remplissez le formulaire**
   - Ajoutez un commentaire (optionnel) : ex. "Test de validation via passerelle"
   - Cliquez sur "Valider" ou "Refuser"

4. **Vérifiez le message de succès**
   - Vous devriez voir "Validation enregistrée"
   - Message : "Votre réponse sera synchronisée prochainement"

### Étape 3 : Vérifier l'enregistrement dans la passerelle

Vérifiez que la validation a bien été enregistrée dans la base SQLite de la passerelle :

```bash
php tests/check_passerelle_db.php
```

Vous devriez voir :
```
Validations enregistrées:
  ID 1: Token a3f77766..., Action: valide, En attente
```

### Étape 4 : Synchroniser vers Agora

**Option A : Via l'interface web**

1. Allez sur http://localhost/agora/public/maintenance
2. Cliquez sur "Test de la Passerelle"
3. Cliquez sur le bouton "Synchroniser maintenant"
4. Attendez 2 secondes (rechargement automatique)
5. Vérifiez le résultat dans le "Test 5: Synchronisation"

**Option B : En ligne de commande**

```bash
cd c:/xampp2/htdocs/agora
php sync-passerelle.php
```

Résultat attendu :
```
✓ Synchronisation réussie
  Validations synchronisées: 1
  Message: 1 validation(s) synchronisée(s)
```

### Étape 5 : Vérifier dans la base de données Agora

Vérifiez que la validation a bien été importée :

```bash
php tests/check_database.php
```

Vous devriez maintenant voir dans "Validations:" :
```
ID 1: Campaign 100, User 3, Action: valide, Date: 2025-10-19 23:XX:XX
```

### Étape 6 : Vérifier dans l'interface web

1. Allez sur http://localhost/agora/public/campaigns/show/100
2. Dans la section "Validateurs", vous devriez voir :
   - Le validateur concerné avec un statut "valide" ou "refuse"
   - Son commentaire si vous en avez ajouté un

### Étape 7 : Vérifier le token utilisé

Relancez le script des tokens :

```bash
php tests/get_validation_urls.php
```

Le token utilisé ne devrait plus apparaître dans la liste (il a été marqué comme `used_at`).

### Étape 8 : Tester la protection contre la double validation

1. **Essayez de réutiliser la même URL** que vous avez testée à l'étape 2
2. Vous devriez voir le message :
   - "Lien déjà utilisé"
   - "Ce lien de validation a déjà été utilisé le DD/MM/YYYY à HH:MM"
   - Action enregistrée affichée

## Vérifications Complètes

### Vérifier les logs de synchronisation

```bash
# Voir les derniers logs
tail -20 logs/sync-passerelle.log

# Ou sur Windows
type logs\sync-passerelle.log
```

### Vérifier l'état complet

Créez ce script de vérification complète :

```bash
php -r "
require 'vendor/autoload.php';
\$dbConfig = require 'config/database.php';
\$db = new Agora\Services\Database(\$dbConfig);

echo '=== ÉTAT COMPLET CAMPAIGN 100 ===\n\n';

// Campagne
\$campaign = \$db->fetch('SELECT * FROM campaigns WHERE id = 100');
echo 'Statut campagne: ' . \$campaign['statut'] . \"\n\n\";

// Tokens
\$tokens = \$db->fetchAll('SELECT user_id, used_at FROM validation_tokens WHERE campaign_id = 100');
echo 'Tokens (' . count(\$tokens) . \" total):\n\";
foreach (\$tokens as \$t) {
    \$status = \$t['used_at'] ? 'Utilisé le ' . \$t['used_at'] : 'Non utilisé';
    echo \"  User {\$t['user_id']}: \$status\n\";
}

// Validations
\$validations = \$db->fetchAll('SELECT user_id, action, commentaire, created_at FROM validations WHERE campaign_id = 100');
echo \"\nValidations (\" . count(\$validations) . \" total):\n\";
foreach (\$validations as \$v) {
    echo \"  User {\$v['user_id']}: {\$v['action']} - {\$v['created_at']}\n\";
    if (\$v['commentaire']) {
        echo \"    Commentaire: {\$v['commentaire']}\n\";
    }
}
"
```

## Tests Additionnels

### Tester un refus

Répétez le processus avec un autre token mais cliquez sur "Refuser" au lieu de "Valider".

### Tester avec commentaire

Testez avec le 3ème token en ajoutant un commentaire détaillé.

### Tester l'expiration

Pour tester un token expiré (nécessite modification manuelle en base) :

```sql
-- Dans phpMyAdmin ou autre client MySQL
UPDATE validation_tokens
SET expires_at = '2025-01-01 00:00:00'
WHERE id = 1;
```

Puis essayez d'utiliser ce token - il devrait être rejeté.

## Résolution de Problèmes

### La validation n'apparaît pas dans la passerelle

```bash
# Vérifier les permissions du fichier SQLite
ls -la passerelle/validation_queue.db

# Sur Windows
dir passerelle\validation_queue.db
```

### La synchronisation échoue

1. Vérifiez la configuration :
```bash
php tests/check_validation_settings.php
```

2. Testez la connectivité :
   - Allez sur http://localhost/agora/public/maintenance/test-passerelle
   - Tous les tests doivent être verts

### Les tokens ne sont pas marqués comme utilisés

Vérifiez que `PasserelleSyncService.php` marque bien les tokens :
- Ligne ~207: `UPDATE validation_tokens SET used_at = NOW() WHERE token = ?`

## Automatisation avec Cron

Une fois le test manuel réussi, configurez le cron pour la synchronisation automatique (voir [CRON_PASSERELLE.md](CRON_PASSERELLE.md)).

## Schéma des Tables

### Base Agora (MySQL)

```sql
-- validation_tokens
id, campaign_id, user_id, token, expires_at, used_at, created_at

-- validations
id, campaign_id, user_id, action, commentaire, created_at
```

### Base Passerelle (SQLite)

```sql
-- validation_responses
id, token, campaign_id, user_id, action, commentaire, validated_at, synced_at
```

## Conclusion

Si tous ces tests passent, votre système de validation par passerelle fonctionne parfaitement de bout en bout !
