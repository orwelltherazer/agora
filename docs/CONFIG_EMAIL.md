# Configuration des emails

## Prérequis

Le système d'envoi d'emails utilise **PHPMailer** et nécessite un serveur SMTP configuré.

## Configuration

**IMPORTANT** : Les paramètres email sont maintenant stockés dans la **base de données** et configurables via l'interface web.

### Accès à la configuration

1. Se connecter en tant qu'administrateur
2. Accéder à **`/settings`** (menu Paramètres)
3. Cliquer sur l'onglet **"Email (SMTP)"**
4. Renseigner les paramètres SMTP :
   - Serveur SMTP (host)
   - Port SMTP (587 pour TLS, 465 pour SSL)
   - Nom d'utilisateur
   - Mot de passe
   - Chiffrement (tls ou ssl)
   - Email expéditeur
   - Nom expéditeur
5. Cliquer sur **Enregistrer**

### Ancienne méthode (obsolète)

❌ Le fichier `config/mail.php` n'est **plus utilisé**. Tous les paramètres sont dans la base de données

## Exemple avec Gmail

1. Activez la validation en 2 étapes sur votre compte Gmail
2. Créez un **mot de passe d'application** : https://myaccount.google.com/apppasswords
3. Dans l'interface `/settings` > Email (SMTP), renseignez :
   - **Serveur SMTP** : `smtp.gmail.com`
   - **Port SMTP** : `587`
   - **Nom d'utilisateur** : `votre-email@gmail.com`
   - **Mot de passe** : `abcd efgh ijkl mnop` (mot de passe d'application à 16 caractères)
   - **Chiffrement** : `tls`
   - **Email expéditeur** : `votre-email@gmail.com`
   - **Nom expéditeur** : `Agora - Communication Municipale`

## Exemple avec un serveur SMTP local ou d'entreprise

Dans l'interface `/settings` > Email (SMTP), renseignez :
- **Serveur SMTP** : `smtp.votre-entreprise.fr`
- **Port SMTP** : `587`
- **Nom d'utilisateur** : `agora@votre-entreprise.fr`
- **Mot de passe** : `votre-mot-de-passe`
- **Chiffrement** : `tls`
- **Email expéditeur** : `noreply@votre-entreprise.fr`
- **Nom expéditeur** : `AGORA - Gestion des Campagnes`

## Fonctionnalités d'emails

Le système envoie automatiquement des emails dans les cas suivants :

### 1. Demande de validation
- **Quand** : Une campagne passe au statut "En validation"
- **Destinataires** : Tous les validateurs assignés à la campagne
- **Contenu** : Détails de la campagne + lien pour consulter et valider

### 2. Relance
- **Quand** : Manuel (à implémenter via un script cron)
- **Destinataires** : Validateurs qui n'ont pas encore validé
- **Contenu** : Rappel de validation en attente

### 3. Changement de statut
- **Quand** : Le statut d'une campagne change
- **Destinataires** : Créateur et parties prenantes
- **Contenu** : Notification du nouveau statut

### 4. Alerte deadline
- **Quand** : Manuel (à implémenter via un script cron)
- **Destinataires** : Responsables de campagnes proches de la deadline
- **Contenu** : Alerte avec nombre de jours restants

## Test de la configuration

Pour tester si les emails fonctionnent :

1. Configurez les paramètres SMTP via `/settings` > Email (SMTP)
2. Accédez à `/maintenance` (menu Maintenance)
3. Cliquez sur **"Test d'envoi d'email"**
4. Vérifiez votre boîte mail

**Autre méthode** :
1. Créez une campagne de test
2. Assignez-vous comme validateur (assurez-vous que votre email est renseigné dans votre profil utilisateur)
3. Passez la campagne en statut "En validation"
4. Vérifiez votre boîte mail

## Dépannage

### Les emails ne partent pas

1. Vérifiez les logs PHP : `c:\xampp2\php\logs\php_error_log`
2. Vérifiez que les paramètres SMTP sont corrects
3. Testez la connexion SMTP avec telnet :
   ```bash
   telnet smtp.gmail.com 587
   ```
4. Vérifiez que votre pare-feu n'est pas le port 587/465

### Erreur "Authentication failed"

- Vérifiez le nom d'utilisateur et mot de passe
- Pour Gmail, utilisez un mot de passe d'application, pas votre mot de passe principal

### Les emails arrivent dans les spams

- Configurez un SPF record pour votre domaine
- Utilisez une adresse email du même domaine que votre serveur
- Évitez d'envoyer trop d'emails à la fois

## Désactivation des emails

Si vous ne souhaitez pas utiliser les emails :

1. Accédez à `/settings` > Email (SMTP)
2. Laissez les champs **Nom d'utilisateur** et **Mot de passe** vides
3. Le système détectera automatiquement que le service mail n'est pas configuré et n'enverra pas d'emails

**Note** : Vous pouvez également supprimer les valeurs existantes pour désactiver temporairement l'envoi d'emails
