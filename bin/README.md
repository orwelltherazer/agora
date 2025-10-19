# Répertoire bin/

Ce répertoire contient les scripts exécutables en ligne de commande.

## Scripts disponibles

### sync-validations.php

**Description** : Script CRON de synchronisation des validations depuis la passerelle externe.

**Usage** :
```bash
php bin/sync-validations.php
```

**Configuration CRON** :

**Linux/Mac** :
```bash
# Toutes les 5 minutes
*/5 * * * * cd /path/to/agora && php bin/sync-validations.php >> /var/log/agora-sync.log 2>&1
```

**Windows (Planificateur de tâches)** :
- Programme : `C:\xampp2\php\php.exe`
- Arguments : `"C:\xampp2\htdocs\agora\bin\sync-validations.php"`
- Déclencheur : Toutes les 5 minutes

**Quand l'utiliser** :
- Uniquement si le mode passerelle est activé (`config('validation.mode') === 'passerelle'`)
- Le script vérifie automatiquement le mode et ne fait rien si le mode direct est actif

**Voir aussi** :
- [Documentation passerelle](../docs/passerelle.md)

## Note importante

Les scripts de migration et de test ont été déplacés :
- **Migrations** : Supprimées (utiliser les dumps SQL pour l'installation)
- **Tests** : Accessibles via l'interface web `/tests` (réservée aux administrateurs)

Pour plus d'informations sur les tests unitaires, consultez la documentation dans `/docs/`.
