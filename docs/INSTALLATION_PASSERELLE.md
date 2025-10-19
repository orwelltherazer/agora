# Installation de la passerelle publique AGORA

## Vue d'ensemble

La passerelle permet aux validateurs d'accéder aux campagnes depuis l'extérieur de l'intranet via `spots.free.nf/agora.php`.

## Architecture

```
Internet
  ↓
spots.free.nf/agora.php (Passerelle publique)
  ↓
Votre serveur intranet (API AGORA)
  ↓
Base de données
```

## Étape 1: Rendre votre serveur intranet accessible

### Option A: Ouvrir un port sur votre box/routeur

1. Accédez à l'interface de votre box Internet
2. Configurez la **redirection de port** (NAT/PAT):
   - Port externe: 8080 (ou autre)
   - Port interne: 80 (Apache)
   - IP locale: L'IP de votre serveur (ex: 192.168.1.100)

3. Notez votre **adresse IP publique**:
   - Visitez https://www.whatismyip.com/
   - Ou tapez: `curl ifconfig.me`

### Option B: Utiliser un service de tunnel (ngrok, localtunnel)

**Avec ngrok (recommandé pour les tests):**

```bash
# Installer ngrok: https://ngrok.com/download
ngrok http 80

# Vous obtiendrez une URL comme:
# https://abc123.ngrok.io
```

## Étape 2: Configuration de l'API AGORA

Éditez `config/app.php` et définissez l'URL publique:

```php
return [
    // ...
    'url' => 'http://VOTRE-IP-PUBLIQUE:8080/agora/public',
    // Ou si vous utilisez ngrok:
    // 'url' => 'https://abc123.ngrok.io/agora/public',
];
```

## Étape 3: Déployer la passerelle

### Fichier à uploader sur spots.free.nf

Uploadez le fichier `passerelle/agora.php` sur votre hébergement:

**Via FTP:**
1. Connectez-vous à `spots.free.nf` via FTP
2. Uploadez `agora.php` à la racine ou dans un dossier public
3. URL finale: `http://spots.free.nf/agora.php`

**Contenu du fichier agora.php:**

Éditez la ligne 11 avec votre URL:

```php
define('AGORA_API_URL', 'http://VOTRE-IP-PUBLIQUE:8080/agora/public/api');
```

**Exemples:**
```php
// Avec IP publique
define('AGORA_API_URL', 'http://86.123.45.67:8080/agora/public/api');

// Avec nom de domaine
define('AGORA_API_URL', 'http://agora.mondomaine.fr/agora/public/api');

// Avec ngrok
define('AGORA_API_URL', 'https://abc123.ngrok.io/agora/public/api');
```

## Étape 4: Tester le système

### Test 1: Vérifier que l'API répond

Depuis votre navigateur, testez:
```
http://VOTRE-IP-PUBLIQUE:8080/agora/public/api/validate/test123
```

Vous devriez voir du JSON avec une erreur "Token invalide" (c'est normal).

### Test 2: Créer une campagne de test

1. Connectez-vous à AGORA (en intranet)
2. Créez une campagne avec vous-même comme validateur
3. Passez-la en validation
4. Récupérez le lien de l'email
5. Remplacez la partie avant `/agora/` par `spots.free.nf/agora.php?token=`

**Exemple:**
```
Email reçu:
http://localhost/agora/public/validate/abc123xyz

Remplacer par:
http://spots.free.nf/agora.php?token=abc123xyz
```

## Étape 5: Mettre à jour les emails

Pour que les emails envoient directement le bon lien:

### Méthode automatique

Créez un nouveau fichier de config `config/external.php`:

```php
<?php
return [
    'enabled' => true,
    'gateway_url' => 'http://spots.free.nf/agora.php?token={token}',
];
```

Modifiez ensuite `CampaignController.php` ligne 360:

```php
// Charger la config externe
$externalConfig = file_exists(__DIR__ . '/../../config/external.php')
    ? require __DIR__ . '/../../config/external.php'
    : ['enabled' => false];

if ($externalConfig['enabled']) {
    // Utiliser la passerelle externe
    $validationUrl = str_replace('{token}', $token, $externalConfig['gateway_url']);
} else {
    // Utiliser l'URL interne (par défaut)
    $validationUrl = $appConfig['url'] . '/validate/' . $token;
}
```

## Sécurité

### Points d'attention

⚠️ **Votre serveur intranet sera accessible depuis Internet**

**Recommandations:**

1. **Filtrer les IPs** dans Apache (.htaccess):
```apache
<Location "/agora/public/api">
    # Autoriser uniquement l'IP de spots.free.nf
    Require ip IP-DU-SERVEUR-SPOTS
</Location>
```

2. **Activer HTTPS** (si possible):
   - Utilisez Let's Encrypt ou un certificat auto-signé
   - Changez `http://` en `https://` dans la config

3. **Limiter les endpoints publics**:
   - Seul `/api/validate/*` doit être accessible
   - Tout le reste doit nécessiter une authentification

4. **Surveiller les logs**:
   - Vérifiez régulièrement les accès à l'API
   - Détectez les tentatives d'accès malveillants

## Alternative: Synchronisation par fichiers

Si vous ne voulez PAS rendre votre serveur intranet accessible:

### Étape A: Export des campagnes

Créez un script qui exporte les campagnes en JSON:

```php
<?php
// export_campaign.php
$token = $argv[1];
$data = $tokenService->validateToken($token);
// ... récupération des données ...
file_put_contents('/export/' . $token . '.json', json_encode($data));
```

### Étape B: Upload manuel vers spots.free.nf

Upload du JSON via FTP vers `spots.free.nf/data/`

### Étape C: Lecture depuis la passerelle

```php
// agora.php
$data = json_decode(file_get_contents('data/' . $token . '.json'), true);
```

**Inconvénient:** Pas de validation en temps réel, nécessite un export manuel.

## Dépannage

### L'API ne répond pas

1. Vérifiez que le port est bien ouvert:
   ```bash
   telnet VOTRE-IP 8080
   ```

2. Vérifiez les logs Apache:
   ```
   tail -f c:\xampp2\apache\logs\access.log
   tail -f c:\xampp2\apache\logs\error.log
   ```

3. Testez en local d'abord:
   ```
   curl http://localhost/agora/public/api/validate/test
   ```

### La passerelle affiche une erreur

1. Activez les erreurs PHP dans agora.php:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

2. Vérifiez que curl est activé sur spots.free.nf

3. Testez l'URL de l'API directement dans votre navigateur

### Les images ne s'affichent pas

- Vérifiez que `/api/download/{token}/{fileId}` fonctionne
- Vérifiez les permissions des dossiers `storage/uploads/`
- Vérifiez que les chemins des fichiers sont corrects

## Support

En cas de problème:
1. Vérifiez les logs (Apache + PHP)
2. Testez l'API directement (sans la passerelle)
3. Vérifiez la configuration réseau (pare-feu, NAT)
