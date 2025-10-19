# Configuration du Cron pour la Passerelle de Validation

## Vue d'ensemble

Pour que la passerelle fonctionne automatiquement, il faut configurer une tâche planifiée (cron) qui synchronise régulièrement les validations depuis la passerelle vers Agora.

## Script de Synchronisation

Le script `sync-passerelle.php` à la racine du projet permet de :
- Récupérer les validations en attente depuis la passerelle
- Les enregistrer dans la base de données Agora
- Marquer les validations comme synchronisées sur la passerelle

## Configuration Manuelle

### Option 1: Cron (Linux/Mac)

1. Ouvrir l'éditeur crontab :
```bash
crontab -e
```

2. Ajouter cette ligne pour exécuter la synchronisation toutes les 5 minutes :
```bash
*/5 * * * * cd /chemin/vers/agora && php sync-passerelle.php >> logs/sync-passerelle.log 2>&1
```

### Option 2: Planificateur de tâches Windows

1. Ouvrir le **Planificateur de tâches Windows**
2. Créer une nouvelle tâche :
   - **Nom** : Synchronisation Passerelle Agora
   - **Déclencheur** : Répéter toutes les 5 minutes
   - **Action** : Démarrer un programme
     - Programme : `C:\xampp\php\php.exe`
     - Arguments : `C:\xampp2\htdocs\agora\sync-passerelle.php`
     - Répertoire de démarrage : `C:\xampp2\htdocs\agora`

### Option 3: Synchronisation manuelle via l'interface

Pour tester ou synchroniser ponctuellement :
1. Aller dans **Maintenance > Test de la Passerelle**
2. Cliquer sur le bouton **"Synchroniser maintenant"**

Ou accéder directement à :
```
http://localhost/agora/sync-passerelle.php
```

## Fréquence Recommandée

- **Production** : Toutes les 5 minutes (équilibre entre réactivité et charge serveur)
- **Développement** : Toutes les 15 minutes ou synchronisation manuelle
- **Forte charge** : Toutes les 2-3 minutes

## Journalisation (Logs)

Les logs de synchronisation sont stockés dans :
```
logs/sync-passerelle.log
```

Pour consulter les derniers logs :
```bash
tail -f logs/sync-passerelle.log
```

## Surveillance

### Vérifier que le cron fonctionne

```bash
# Voir les dernières lignes du log
tail -20 logs/sync-passerelle.log

# Vérifier la date de dernière synchronisation
ls -lh logs/sync-passerelle.log
```

### Tester manuellement

```bash
cd /chemin/vers/agora
php sync-passerelle.php
```

## Codes de Retour

Le script retourne :
- `0` : Synchronisation réussie
- `1` : Erreur (configuration manquante, connexion échouée, etc.)

## Dépannage

### Le cron ne s'exécute pas

1. Vérifier les permissions du fichier :
```bash
chmod +x sync-passerelle.php
```

2. Vérifier le chemin de PHP :
```bash
which php
```

3. Tester en ligne de commande :
```bash
php sync-passerelle.php
```

### Pas de validations synchronisées

1. Vérifier la configuration dans **Paramètres > Validation** :
   - Mode = `passerelle`
   - URL de la passerelle configurée
   - Clé API configurée

2. Tester la connectivité :
   - Aller dans **Maintenance > Test de la Passerelle**
   - Vérifier que tous les tests passent

3. Vérifier qu'il y a des validations en attente sur la passerelle

### Erreurs de connexion

1. Vérifier que la passerelle est accessible :
```bash
curl -H "Authorization: Bearer VOTRE_CLE_API" \
     https://passerelle.example.com/api.php/health
```

2. Vérifier les logs :
```bash
tail -100 logs/sync-passerelle.log
```

## Exemple de Configuration Complète

### Crontab complet

```cron
# Synchronisation passerelle toutes les 5 minutes
*/5 * * * * cd /var/www/agora && php sync-passerelle.php >> logs/sync-passerelle.log 2>&1

# Nettoyage des tokens expirés une fois par jour à 2h du matin
0 2 * * * cd /var/www/agora && php artisan tokens:clean >> logs/tokens-clean.log 2>&1
```

## Monitoring et Alertes

Pour recevoir des alertes en cas d'erreur, vous pouvez :

1. Utiliser un service de monitoring (exemple avec curl) :
```bash
*/5 * * * * cd /var/www/agora && php sync-passerelle.php >> logs/sync-passerelle.log 2>&1 || curl -X POST https://monitoring.example.com/alert
```

2. Envoyer un email en cas d'échec :
```bash
*/5 * * * * cd /var/www/agora && php sync-passerelle.php >> logs/sync-passerelle.log 2>&1 || mail -s "Erreur sync passerelle" admin@example.com
```

## Sécurité

- Le script vérifie automatiquement que le mode passerelle est activé
- Assurez-vous que le fichier de logs n'est pas accessible publiquement
- Protégez la clé API de la passerelle

## Support

Pour plus d'informations, consultez :
- [Documentation de la passerelle](passerelle.md)
- [Test de la passerelle](http://localhost/agora/public/maintenance/test-passerelle)
