# AGORA - Descriptif Fonctionnel

## Vue d'ensemble

**AGORA** est une application web de gestion et de planification de campagnes de communication. Elle permet de centraliser la création, la validation et la publication de campagnes sur différents supports de communication (numériques et physiques).

### Objectif

Faciliter la coordination entre les demandeurs (associations, services, élus), les créateurs de campagnes, les validateurs et les gestionnaires de supports de communication.

### Utilisateurs Cibles

- **Administrateurs** : Gestion complète du système, utilisateurs et paramètres
- **Créateurs de campagnes** : Services internes, associations, élus
- **Validateurs** : Responsables chargés de valider les campagnes
- **Consultants** : Lecture seule des campagnes

## Concepts Métier

### 1. Campagne

Une campagne représente une opération de communication pour promouvoir un événement, une information ou une action. Elle contient :

- **Informations générales** :
  - Titre
  - Description
  - Demandeur (nom de l'association, service, élu)
  - Email du demandeur

- **Dates** :
  - Date de début de l'événement
  - Date de fin de l'événement (optionnelle pour événement d'un jour)

- **Supports de diffusion** :
  - Liste des supports sélectionnés (écrans, panneaux, réseaux sociaux, etc.)
  - Pour chaque support : dates de début et fin de publication

- **Fichiers joints** :
  - Visuels, documents, médias
  - Versioning automatique

- **Validateurs** :
  - Liste des personnes devant valider la campagne
  - Validation parallèle (tous doivent valider, aucun ordre imposé)

### 2. Support de Communication

Un support est un canal de diffusion pour les campagnes :

- **Types** :
  - Numérique : écrans LED, réseaux sociaux, site web, newsletter
  - Physique : panneaux d'affichage, flyers, affiches

- **Propriétés** :
  - Nom
  - Type (numérique/physique)
  - Capacité maximale (nombre de campagnes simultanées, peut être illimité)
  - Ordre d'affichage (pour l'interface)
  - Statut actif/inactif

### 3. Statuts de Campagne

Le cycle de vie d'une campagne suit ces statuts :

1. **Brouillon** : Campagne en cours de création, non soumise
2. **En validation** : Soumise aux validateurs, en attente de leurs réponses
3. **Validée** : Tous les validateurs ont approuvé
4. **Refusée** : Au moins un validateur a refusé
5. **Publiée** : Campagne validée et activement diffusée
6. **Archivée** : Campagne terminée ou archivée manuellement
7. **Annulée** : Campagne annulée par le créateur ou un administrateur

### 4. Validation

Processus d'approbation des campagnes :

- **Validation parallèle** : Tous les validateurs désignés doivent valider (aucun ordre séquentiel)
- **Méthodes de validation** :
  - Via l'interface Agora (si accès au réseau interne)
  - Par email avec lien sécurisé (token unique à usage unique)
  - Via passerelle externe (si Agora sur intranet et validateurs externes)

- **Actions possibles** :
  - Valider (avec commentaire optionnel)
  - Refuser (avec motif)

- **Règles** :
  - Un seul refus suffit pour que la campagne passe en statut "Refusée"
  - Tous doivent valider pour que la campagne passe en statut "Validée"
  - Les tokens de validation expirent après X jours (configurable)

## Fonctionnalités Principales

### 1. Tableau de Bord (Dashboard)

**URL** : `/dashboard`

**Affichage** :
- Statistiques en temps réel :
  - Nombre total de campagnes actives
  - Campagnes en validation
  - Campagnes validées en attente de publication
  - Campagnes publiées actuellement

- **Campagnes en attente de ma validation** :
  - Liste des campagnes assignées à l'utilisateur connecté nécessitant une validation
  - Bouton d'accès rapide

- **Campagnes à venir** :
  - Campagnes validées dont la date de début d'événement est proche
  - Triées par date

**Permissions** : Tous les utilisateurs connectés

---

### 2. Gestion des Campagnes

#### 2.1. Liste des Campagnes

**URL** : `/campaigns`

**Fonctionnalités** :
- Affichage en tableau avec :
  - Titre
  - Demandeur
  - Date de l'événement
  - Statut (badge coloré)
  - Créateur
  - Actions (voir, modifier, archiver)

- **Filtres** :
  - Par statut (brouillon, en validation, validée, etc.)
  - Recherche textuelle (titre, demandeur)
  - Affichage des campagnes archivées

- **Actions groupées** :
  - Export (à venir)

**Permissions** : Tous les utilisateurs connectés

#### 2.2. Création de Campagne

**URL** : `/campaigns/create`

**Formulaire** :
- Informations générales :
  - Titre (requis)
  - Description
  - Demandeur (requis)
  - Email du demandeur

- Dates de l'événement :
  - Date de début (requise)
  - Date de fin (optionnelle)

- Supports de communication :
  - Sélection multiple avec dates de publication par support
  - Pour chaque support :
    - Date début de publication (requise)
    - Date fin de publication (optionnelle)

- Validateurs :
  - Sélection multiple des validateurs
  - Recherche par nom/prénom

- Fichiers :
  - Upload multiple
  - Types acceptés : images, PDF, documents Office
  - Taille maximale configurable

**Actions** :
- **Enregistrer en brouillon** : Sauvegarde sans soumettre à validation
- **Soumettre pour validation** : Change le statut à "en_validation" et envoie les emails

**Permissions** : Créateurs et administrateurs

#### 2.3. Modification de Campagne

**URL** : `/campaigns/edit/{id}`

**Fonctionnalités** :
- Même formulaire que la création
- Pré-rempli avec les données existantes
- **Restrictions** :
  - Les campagnes validées/publiées ne peuvent plus être modifiées (sauf par admin)
  - Les campagnes en validation peuvent être modifiées (relance le processus)

- **Gestion des fichiers** :
  - Visualisation des fichiers existants
  - Suppression de fichiers
  - Ajout de nouveaux fichiers

- **Historique** :
  - Journal des modifications (qui, quand, quoi)

**Permissions** : Créateur de la campagne ou administrateur

#### 2.4. Détail de Campagne

**URL** : `/campaigns/show/{id}`

**Affichage en sections** :

1. **En-tête** :
   - Titre
   - Statut (badge)
   - Dates de l'événement
   - Créateur et date de création

2. **Informations** :
   - Description complète
   - Demandeur
   - Contact du demandeur

3. **Supports de diffusion** :
   - Liste des supports sélectionnés
   - Dates de publication par support
   - Statut de publication (planifié, en cours, terminé)

4. **Fichiers joints** :
   - Miniatures pour images
   - Icônes pour documents
   - Nom, taille, date d'upload
   - Bouton de téléchargement
   - Bouton de suppression (si permissions)

5. **Validateurs** :
   - Liste des validateurs assignés
   - Statut de chaque validation :
     - En attente (icône horloge)
     - Validé (icône check verte)
     - Refusé (icône croix rouge)
   - Commentaires des validateurs

6. **Actions** :
   - Modifier (si permissions)
   - Supprimer (si permissions et statut brouillon)
   - Archiver/Désarchiver
   - Dupliquer
   - Renvoyer les emails de validation

**Permissions** : Tous les utilisateurs connectés (lecture), créateur ou admin (modification)

#### 2.5. Archivage / Désarchivage

**URLs** :
- `/campaigns/archive/{id}` (POST)
- `/campaigns/unarchive/{id}` (POST)

**Comportement** :
- Archiver :
  - Change le statut à "archivee"
  - La campagne disparaît des listes par défaut
  - Enregistre un log avec date et utilisateur

- Désarchiver :
  - Restaure le statut précédent
  - La campagne réapparaît dans les listes

**Permissions** : Créateur ou administrateur

---

### 3. Calendrier

**URL** : `/calendar`

**Visualisation** :
- Vue calendrier mensuel (type FullCalendar)
- Chaque campagne est représentée par un événement
- Couleur selon le statut
- Clic sur événement → détail de la campagne

**Filtres** :
- Par support (sélection multiple)
- Par statut
- Vue mois / semaine / jour

**Affichage des campagnes** :
- Par défaut : dates de l'événement
- Optionnel : dates de publication par support (si filtre support actif)

**Permissions** : Tous les utilisateurs connectés

---

### 4. Validation de Campagnes

#### 4.1. Validation Interne (Interface Agora)

**URL** : `/campaigns/show/{id}` (section validation)

**Affichage** :
- Informations complètes de la campagne
- Formulaire de validation si l'utilisateur est validateur assigné

**Actions** :
- **Valider** : Bouton vert
- **Refuser** : Bouton rouge avec champ commentaire obligatoire

**Comportement** :
- Enregistre la validation dans la base
- Met à jour le statut global de la campagne si tous ont répondu
- Envoie une notification au créateur
- Enregistre un log d'événement

#### 4.2. Validation Externe (Par Email)

**URL** : `/validate/{token}`

**Processus** :
1. Le validateur reçoit un email avec lien unique
2. Clic sur le lien → page de validation publique (pas de login requis)
3. Affichage :
   - Informations de la campagne
   - Fichiers (téléchargeables)
   - Formulaire de validation/refus
4. Soumission → enregistrement et message de confirmation

**Sécurité** :
- Token unique à usage unique (64 caractères aléatoires)
- Expiration après X jours (configurable)
- Token marqué comme utilisé après validation
- Vérification de validité à chaque accès

**Mode Passerelle** :
- Si Agora est sur intranet, le lien pointe vers une passerelle externe
- La passerelle stocke temporairement la validation
- Un CRON synchronise les validations toutes les 5 minutes
- Voir [documentation passerelle](passerelle.md)

#### 4.3. API de Validation

**URL** : `/api/validate/{token}`

**Endpoints** :
- `GET /api/validate/{token}/data` : Récupère les données de la campagne (JSON)
- `POST /api/validate/{token}/submit` : Soumet une validation
- `GET /api/validate/{token}/file/{fileId}` : Télécharge un fichier

**Usage** : Permet une intégration avec des systèmes tiers ou applications mobiles

---

### 5. Gestion des Utilisateurs

**URL** : `/users`

#### 5.1. Liste des Utilisateurs

**Affichage** :
- Tableau avec :
  - Nom complet
  - Email
  - Rôles
  - Statut (actif/inactif)
  - Actions (modifier, supprimer)

- **Filtres** :
  - Recherche textuelle (nom, prénom, email)
  - Par statut (actif/inactif)
  - Par rôle

**Permissions** : Administrateurs uniquement

#### 5.2. Création d'Utilisateur

**URL** : `/users/create`

**Formulaire** :
- Email (unique, requis)
- Mot de passe (requis, min 8 caractères)
- Nom (requis)
- Prénom (requis)
- Téléphone (optionnel)
- Rôles (sélection multiple) :
  - Administrateur
  - Créateur
  - Validateur
  - Consultant
- Statut actif (case à cocher)

**Validation** :
- Email unique dans la base
- Format email valide
- Mot de passe sécurisé (longueur minimale)

**Permissions** : Administrateurs uniquement

#### 5.3. Modification d'Utilisateur

**URL** : `/users/edit/{id}`

**Fonctionnalités** :
- Même formulaire que création
- Mot de passe optionnel (si vide, inchangé)
- Activation/désactivation du compte
- Modification des rôles

**Restrictions** :
- Un administrateur ne peut pas se retirer lui-même le rôle admin (si dernier admin)

**Permissions** : Administrateurs uniquement

#### 5.4. Suppression d'Utilisateur

**URL** : `/users/delete/{id}` (POST)

**Comportement** :
- Confirmation requise
- Suppression logique (marque comme inactif) ou physique selon configuration
- Vérifications :
  - Ne peut pas supprimer le dernier administrateur
  - Alerte si utilisateur a des campagnes en cours

**Permissions** : Administrateurs uniquement

---

### 6. Gestion des Supports

**URL** : `/supports`

#### 6.1. Liste des Supports

**Affichage** :
- Tableau avec :
  - Nom
  - Type (numérique/physique)
  - Capacité maximale
  - Ordre d'affichage
  - Statut (actif/inactif)
  - Actions

- Triés par ordre d'affichage

**Permissions** : Tous les utilisateurs (lecture), administrateurs (modification)

#### 6.2. Création de Support

**URL** : `/supports/create`

**Formulaire** :
- Nom (requis)
- Type : numérique ou physique (requis)
- Capacité maximale (optionnel, nombre entier)
  - Si vide : illimité
  - Si défini : nombre maximum de campagnes simultanées
- Ordre d'affichage (nombre)
- Actif (case à cocher)

**Permissions** : Administrateurs uniquement

#### 6.3. Modification de Support

**URL** : `/supports/edit/{id}`

**Fonctionnalités** :
- Même formulaire que création
- Alerte si réduction de capacité impacte des campagnes existantes

**Permissions** : Administrateurs uniquement

#### 6.4. Suppression de Support

**URL** : `/supports/delete/{id}` (POST)

**Restrictions** :
- Impossible de supprimer un support utilisé dans des campagnes actives
- Possibilité de désactiver plutôt que supprimer

**Permissions** : Administrateurs uniquement

---

### 7. Maintenance et Administration

**URL** : `/maintenance`

**Menu** : Visible uniquement pour les administrateurs

#### 7.1. Tableau de Bord Maintenance

**Affichage** :
- Informations système :
  - Version de l'application
  - Base de données (taille, nombre de tables)
  - Espace disque utilisé pour les fichiers

- Outils rapides :
  - Test d'envoi d'email
  - Nettoyage des fichiers orphelins
  - Vérification de l'intégrité des données
  - Accès au journal d'événements

**Permissions** : Administrateurs uniquement

#### 7.2. Journal d'Événements

**URL** : `/maintenance/logs`

**Fonctionnalités** :
- Trace exhaustive de toutes les opérations sur les campagnes
- Affichage en tableau :
  - Date/heure
  - Campagne concernée
  - Utilisateur
  - Action
  - Description détaillée
  - Anciennes/nouvelles valeurs (JSON)

- **Filtres** :
  - Recherche textuelle (titre campagne, description)
  - Par utilisateur
  - Par type d'action :
    - created : Création
    - updated : Modification
    - status_changed : Changement de statut
    - archived : Archivage
    - unarchived : Désarchivage
    - file_deleted : Suppression de fichier
    - files_added : Ajout de fichiers
    - validated : Validation
    - rejected : Refus

- **Pagination** : 50 entrées par page

**Types d'événements tracés** :
- Création de campagne
- Modification (avec détail des champs modifiés)
- Changement de statut
- Archivage/désarchivage
- Ajout/suppression de fichiers
- Validations (directes et synchronisées depuis passerelle)
- Refus de validation

**Format des logs** :
```json
{
  "id": 123,
  "campaign_id": 42,
  "user_id": 5,
  "action": "updated",
  "description": "Modification de la campagne (titre, description)",
  "old_values": {
    "titre": "Ancien titre",
    "description": "Ancienne description"
  },
  "new_values": {
    "titre": "Nouveau titre",
    "description": "Nouvelle description"
  },
  "created_at": "2025-10-19 14:30:00"
}
```

**Permissions** : Administrateurs uniquement

#### 7.3. Test d'Email

**URL** : `/maintenance/test-email`

**Fonctionnalités** :
- Formulaire simple :
  - Adresse email destinataire
  - Sujet
  - Message

- Envoie un email de test avec la configuration SMTP actuelle
- Affiche le résultat (succès/erreur avec détails)
- Utile pour vérifier la configuration mail

**Permissions** : Administrateurs uniquement

---

### 8. Paramètres

**URL** : `/settings`

**Configuration globale** :

#### 8.1. Paramètres Généraux
- Nom de l'application
- URL de base
- Logo
- Fuseau horaire

#### 8.2. Paramètres Email
- Serveur SMTP (hôte, port)
- Authentification (utilisateur, mot de passe)
- Email expéditeur par défaut
- Nom de l'expéditeur
- Activation TLS/SSL

#### 8.3. Paramètres de Validation
- **Mode de validation** :
  - `direct` : Liens de validation pointent directement vers Agora
  - `passerelle` : Liens pointent vers une passerelle externe

- **URL de la passerelle** (si mode passerelle)
- **Clé API de la passerelle** (pour sécuriser les échanges)
- **Durée de validité des tokens** (en jours)

#### 8.4. Paramètres de Fichiers
- Taille maximale d'upload (Mo)
- Types de fichiers autorisés
- Chemin de stockage

**Permissions** : Administrateurs uniquement

---

### 9. Authentification et Sécurité

#### 9.1. Connexion

**URL** : `/login`

**Fonctionnalités** :
- Formulaire email/mot de passe
- Validation serveur
- Création de session
- Redirection vers le dashboard ou page demandée

**Sécurité** :
- Mot de passe hashé (bcrypt)
- Protection CSRF
- Limitation des tentatives (à implémenter)

#### 9.2. Déconnexion

**URL** : `/logout`

**Comportement** :
- Destruction de la session
- Redirection vers la page de login

#### 9.3. Gestion des Rôles

**Rôles disponibles** :

| Rôle | Permissions |
|------|-------------|
| **Administrateur** | Accès total : gestion utilisateurs, supports, paramètres, maintenance |
| **Créateur** | Créer, modifier ses campagnes, voir toutes les campagnes |
| **Validateur** | Voir les campagnes, valider les campagnes assignées |
| **Consultant** | Lecture seule sur les campagnes |

**Système de permissions** :
- Basé sur les rôles (RBAC - Role-Based Access Control)
- Un utilisateur peut avoir plusieurs rôles
- Vérifications à chaque requête via middleware Auth
- Session stocke les rôles de l'utilisateur

---

### 10. Gestion des Fichiers

#### 10.1. Upload de Fichiers

**Processus** :
- Upload via formulaire multipart
- Validation :
  - Type de fichier (MIME type)
  - Taille maximale
  - Nom de fichier sécurisé

- Stockage :
  - Organisation par année/mois : `/uploads/2025/10/`
  - Nom unique généré (UUID + extension)
  - Métadonnées sauvegardées en base

#### 10.2. Téléchargement de Fichiers

**URL** : `/files/{year}/{month}/{filename}`

**Sécurité** :
- Vérification des permissions (utilisateur connecté ou token valide)
- Pas d'accès direct aux fichiers (passent par le contrôleur)
- Headers de sécurité (Content-Disposition, Content-Type)

#### 10.3. Suppression de Fichiers

**Comportement** :
- Suppression physique du fichier
- Suppression de l'entrée en base
- Log de l'action
- Vérification des permissions (créateur ou admin)

#### 10.4. Versioning

**Fonctionnalité** (à venir) :
- Système de versions pour les fichiers
- Historique des modifications
- Possibilité de revenir à une version antérieure

---

## Workflows Métier

### Workflow 1 : Création et Validation d'une Campagne

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. CRÉATION                                                     │
│    Créateur remplit le formulaire et soumet                     │
│    Statut : brouillon → en_validation                           │
└────────────┬────────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. NOTIFICATION                                                 │
│    Emails envoyés aux validateurs avec liens uniques            │
│    Création des tokens de validation                            │
└────────────┬────────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. VALIDATION PARALLÈLE                                         │
│    Chaque validateur valide ou refuse indépendamment            │
│    ┌──────────────┐     ┌──────────────┐     ┌──────────────┐  │
│    │ Validateur 1 │     │ Validateur 2 │     │ Validateur 3 │  │
│    │   Valide ✓   │     │   Valide ✓   │     │   Valide ✓   │  │
│    └──────────────┘     └──────────────┘     └──────────────┘  │
└────────────┬────────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. VÉRIFICATION AUTOMATIQUE                                     │
│    Tous ont validé ?                                            │
│    OUI → Statut : validee                                       │
│    NON (au moins un refus) → Statut : refusee                   │
└────────────┬────────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. NOTIFICATION CRÉATEUR                                        │
│    Email de notification du résultat                            │
└────────────┬────────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. PUBLICATION (manuelle)                                       │
│    Administrateur change le statut à "publiee"                  │
│    Diffusion sur les supports aux dates prévues                 │
└─────────────────────────────────────────────────────────────────┘
```

### Workflow 2 : Validation avec Passerelle (Mode Intranet)

```
┌──────────────────────────────────────────────────────────────────┐
│                        INTRANET (Agora)                          │
└────────────┬─────────────────────────────────────────────────────┘
             │
             │ 1. Création campagne, envoi emails
             │    URL : https://passerelle.com/validate/{token}
             │
             ▼
┌──────────────────────────────────────────────────────────────────┐
│                    INTERNET (Passerelle)                         │
│  2. Validateur clique sur le lien                                │
│  3. Affichage formulaire de validation                           │
│  4. Validation soumise                                           │
│  5. Stockage dans SQLite (synced = 0)                            │
└────────────┬─────────────────────────────────────────────────────┘
             │
             │ Attente (max 5 minutes)
             │
             ▼
┌──────────────────────────────────────────────────────────────────┐
│                    INTRANET (Agora - CRON)                       │
│  6. CRON exécute bin/sync-validations.php                        │
│  7. Appel API passerelle : GET /pending-validations              │
│  8. Récupération des validations (synced = 0)                    │
│  9. Traitement dans Agora :                                      │
│     - Marque token utilisé                                       │
│     - Enregistre dans campaign_validations                       │
│     - Enregistre log "synchronisé depuis passerelle"             │
│     - Vérifie statut campagne                                    │
│  10. Confirmation à passerelle : POST /sync-completed            │
└────────────┬─────────────────────────────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────────────────────────────┐
│                    INTERNET (Passerelle)                         │
│  11. Marque validations comme synchronisées (synced = 1)         │
└──────────────────────────────────────────────────────────────────┘
```

### Workflow 3 : Modification d'une Campagne en Validation

```
┌─────────────────────────────────────────────────────────────────┐
│ Campagne en statut : en_validation                              │
│ 3 validateurs, 2 ont déjà validé                                │
└────────────┬────────────────────────────────────────────────────┘
             │
             │ Créateur modifie la campagne
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ SYSTÈME RÉINITIALISE LE PROCESSUS                              │
│ - Supprime toutes les validations existantes                    │
│ - Génère de nouveaux tokens                                     │
│ - Renvoie emails à TOUS les validateurs                         │
│ - Log : "Modification → relance validation"                     │
└────────────┬────────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ Les validateurs doivent valider à nouveau                       │
│ (même ceux qui avaient déjà validé)                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## Règles Métier

### 1. Gestion des Dates

- **Date événement** :
  - `date_event_debut` : obligatoire
  - `date_event_fin` : optionnelle
  - Si `date_event_fin` est null → événement d'un jour
  - `date_event_fin` doit être >= `date_event_debut`

- **Date publication** :
  - Pour chaque support : `date_pub_debut` obligatoire
  - `date_pub_fin` optionnelle
  - Si `date_pub_fin` null → publication jusqu'à nouvel ordre
  - Recommandation : `date_pub_debut` <= `date_event_debut`

### 2. Capacité des Supports

- Si un support a une capacité maximale définie :
  - Le système compte les campagnes actives sur ce support pour une période donnée
  - Alerte si capacité dépassée (mais n'empêche pas la création)
  - Vue `v_support_availability` permet de consulter la disponibilité

### 3. Permissions et Visibilité

- **Campagnes brouillon** :
  - Visibles uniquement par le créateur et les administrateurs

- **Campagnes en validation/validées/publiées** :
  - Visibles par tous les utilisateurs connectés

- **Campagnes archivées** :
  - Cachées par défaut dans les listes
  - Filtre "Afficher archivées" pour les voir

- **Modification** :
  - Créateur peut modifier ses campagnes en statut brouillon ou en_validation
  - Administrateurs peuvent modifier toutes les campagnes
  - Campagnes validées/publiées : modification limitée (ou interdite selon config)

### 4. Validation

- **Système parallèle** :
  - Tous les validateurs reçoivent l'email simultanément
  - Aucun ordre imposé
  - Chacun peut valider indépendamment des autres

- **Règles de changement de statut** :
  - Si au moins 1 refus → statut `refusee` (immédiat)
  - Si tous validés → statut `validee`
  - Tant que certains n'ont pas répondu → statut `en_validation`

- **Token de validation** :
  - Unique et aléatoire (64 caractères)
  - À usage unique (marqué `used = 1` après utilisation)
  - Expiration configurable (par défaut 30 jours)
  - Vérifié à chaque accès

### 5. Fichiers

- **Stockage** :
  - Organisation : `/uploads/{année}/{mois}/{nom_unique}`
  - Nom original conservé en base
  - Nom de stockage : UUID + extension

- **Sécurité** :
  - Validation du type MIME
  - Whitelist des extensions autorisées
  - Taille maximale configurable
  - Pas d'accès direct (via contrôleur uniquement)

- **Suppression** :
  - Suppression physique du fichier
  - Suppression de l'entrée base de données
  - Log de l'action (traçabilité)

---

## Modèle de Données

### Tables Principales

#### campaigns
Campagnes de communication.

**Colonnes clés** :
- `id` : Identifiant unique
- `titre` : Titre de la campagne
- `description` : Description détaillée
- `demandeur` : Nom de l'association, service, élu
- `demandeur_email` : Email du demandeur
- `date_event_debut` : Date de début de l'événement
- `date_event_fin` : Date de fin (nullable)
- `statut` : brouillon, en_validation, validee, publiee, archivee, refusee, annulee
- `campagne_source_id` : ID de la campagne source (si dupliquée)
- `created_by` : ID de l'utilisateur créateur
- Timestamps : `created_at`, `updated_at`, `validated_at`, `published_at`, `archived_at`

#### campaign_supports
Association campagne ↔ support avec dates de publication.

**Colonnes clés** :
- `campaign_id` : FK vers campaigns
- `support_id` : FK vers supports
- `date_pub_debut` : Date début de publication
- `date_pub_fin` : Date fin de publication (nullable)
- `statut_publication` : planifie, en_cours, termine

**Contrainte** : UNIQUE(campaign_id, support_id)

#### campaign_validators
Validateurs assignés à une campagne.

**Colonnes clés** :
- `campaign_id` : FK vers campaigns
- `user_id` : FK vers users
- `ordre` : Ordre d'affichage (non utilisé pour séquence)
- `assigned_at` : Date d'assignation

**Contrainte** : UNIQUE(campaign_id, user_id)

#### validations (ou campaign_validations)
Réponses de validation (approuver/refuser).

**Colonnes clés** :
- `campaign_id` : FK vers campaigns
- `user_id` : FK vers users (validateur)
- `action` : valide, refuse, demande_modification
- `commentaire` : Commentaire optionnel
- `validated_at` : Date/heure de la validation

#### validation_tokens
Tokens pour validation par email.

**Colonnes clés** :
- `campaign_id` : FK vers campaigns
- `user_id` : FK vers users (validateur)
- `token` : Token unique (64 caractères)
- `expires_at` : Date d'expiration
- `used` : Booléen (0 = non utilisé, 1 = utilisé)
- `used_at` : Date d'utilisation

**Contrainte** : UNIQUE(token)

#### campaign_logs
Journal exhaustif des événements.

**Colonnes clés** :
- `campaign_id` : FK vers campaigns
- `user_id` : FK vers users (auteur de l'action)
- `action` : Type d'action (created, updated, validated, rejected, etc.)
- `description` : Description textuelle
- `old_values` : JSON des anciennes valeurs
- `new_values` : JSON des nouvelles valeurs
- `created_at` : Date/heure de l'événement

#### supports
Supports de communication.

**Colonnes clés** :
- `nom` : Nom du support
- `type` : numerique ou physique
- `capacite_max` : Nombre max de campagnes simultanées (nullable = illimité)
- `actif` : Booléen
- `ordre_affichage` : Ordre d'affichage dans les listes

#### files
Fichiers joints aux campagnes.

**Colonnes clés** :
- `campaign_id` : FK vers campaigns
- `nom_original` : Nom du fichier original
- `nom_stockage` : Nom unique sur le serveur
- `chemin` : Chemin complet
- `type_mime` : Type MIME
- `taille` : Taille en octets
- `version` : Numéro de version (pour versioning)
- `est_version_actuelle` : Booléen
- `uploaded_by` : FK vers users
- `uploaded_at` : Date d'upload

#### users
Utilisateurs de l'application.

**Colonnes clés** :
- `email` : Email unique (login)
- `password` : Mot de passe hashé (bcrypt)
- `nom`, `prenom` : Identité
- `telephone` : Téléphone (optionnel)
- `actif` : Booléen (compte actif/inactif)

#### roles
Rôles disponibles.

**Valeurs** :
- Administrateur
- Créateur
- Validateur
- Consultant

#### user_roles
Association utilisateur ↔ rôle (relation many-to-many).

#### comments
Commentaires sur les campagnes.

**Colonnes clés** :
- `campaign_id` : FK vers campaigns
- `user_id` : FK vers users
- `parent_id` : FK vers comments (pour threads)
- `contenu` : Texte du commentaire

#### notifications
Notifications aux utilisateurs.

**Colonnes clés** :
- `user_id` : Destinataire
- `campaign_id` : Campagne concernée (nullable)
- `type` : validation_demandee, validation_accordee, validation_refusee, etc.
- `titre`, `message` : Contenu
- `est_lu` : Booléen
- `email_envoye` : Booléen
- `email_envoye_at` : Date d'envoi email

#### activity_logs
Logs d'activité générale (connexions, actions système).

**Colonnes clés** :
- `user_id` : Utilisateur (nullable)
- `campaign_id` : Campagne (nullable)
- `action` : Type d'action
- `details` : Détails de l'action
- `ip_address` : Adresse IP
- `user_agent` : User agent

#### settings
Paramètres globaux de l'application.

**Colonnes clés** :
- `cle` : Clé unique du paramètre
- `valeur` : Valeur (text)
- `type` : string, integer, boolean, json
- `description` : Description du paramètre

---

### Vues Métier

#### v_campaign_validation_status
Vue synthétisant l'état de validation des campagnes.

**Colonnes** :
- `campaign_id` : ID de la campagne
- `titre` : Titre de la campagne
- `statut` : Statut actuel
- `nb_validateurs` : Nombre total de validateurs assignés
- `nb_validations` : Nombre de validations reçues
- `tous_valide` : Booléen (1 si tous ont validé, 0 sinon)

**Usage** : Affichage rapide de l'état des validations sur le dashboard.

#### v_support_availability
Vue de disponibilité des supports.

**Colonnes** :
- `support_id` : ID du support
- `support_nom` : Nom du support
- `capacite_max` : Capacité maximale
- `date_pub_debut`, `date_pub_fin` : Dates de publication
- `campagnes_actives` : Nombre de campagnes actives sur la période

**Usage** : Vérifier la disponibilité d'un support avant d'ajouter une campagne.

---

## Architecture Technique

### Stack Technologique

- **Backend** : PHP 7.4+ (POO, namespaces)
- **Base de données** : MySQL/MariaDB
- **Template engine** : Twig
- **Frontend** : HTML5, CSS3 (Tailwind CSS), JavaScript (Vanilla)
- **Serveur web** : Apache/Nginx
- **Email** : PHPMailer avec SMTP

### Structure MVC

```
src/
├── Controllers/          # Contrôleurs (logique métier)
│   ├── AuthController.php
│   ├── CampaignController.php
│   ├── UsersController.php
│   ├── SupportsController.php
│   ├── CalendarController.php
│   ├── DashboardController.php
│   ├── MaintenanceController.php
│   ├── SettingsController.php
│   ├── FileController.php
│   ├── PublicValidationController.php
│   └── ApiValidationController.php
│
├── Repositories/         # Accès aux données
│   ├── CampaignRepository.php
│   ├── UserRepository.php
│   └── SupportRepository.php
│
├── Services/             # Services métier
│   ├── Database.php               # Connexion PDO
│   ├── MailService.php            # Envoi d'emails
│   ├── CampaignLogService.php     # Logs d'événements
│   └── PasserelleSyncService.php  # Synchronisation passerelle
│
├── Middleware/           # Middleware
│   └── Auth.php          # Authentification/autorisation
│
└── Helpers/              # Fonctions utilitaires
    └── functions.php
```

### Sécurité

- **Authentification** : Session PHP, mot de passe bcrypt
- **Autorisation** : Middleware Auth avec vérification des rôles
- **Protection CSRF** : Tokens CSRF sur tous les formulaires (à implémenter)
- **Validation des entrées** : Côté serveur pour toutes les données
- **Protection XSS** : Échappement automatique via Twig (autoescape)
- **Protection SQL Injection** : Requêtes préparées PDO
- **Fichiers** : Validation type MIME, whitelist extensions, stockage hors webroot

---

## Cas d'Usage Détaillés

### Cas 1 : Une association veut promouvoir un événement

**Acteurs** : Marie (association), Paul (validateur communication), Sophie (validateur direction)

**Scénario** :
1. Marie se connecte à Agora
2. Crée une nouvelle campagne :
   - Titre : "Festival de printemps 2025"
   - Événement du 15 au 17 mai 2025
   - Demandeur : Association culturelle locale
   - Description complète de l'événement
3. Sélectionne les supports :
   - Écran LED vitrine (publication du 1er au 20 mai)
   - Facebook (publication du 1er mai au 17 mai)
   - Panneau d'affichage (publication du 5 au 20 mai)
4. Upload des visuels (affiche, bannière Facebook)
5. Assigne Paul et Sophie comme validateurs
6. Soumet pour validation

**Système** :
- Enregistre la campagne (statut : en_validation)
- Génère 2 tokens uniques
- Envoie emails à Paul et Sophie avec liens de validation

**Paul** :
- Reçoit l'email, clique sur le lien
- Consulte la campagne, télécharge les visuels
- Valide avec commentaire : "Visuels conformes à la charte"

**Sophie** :
- Reçoit l'email, clique sur le lien
- Valide également

**Système** :
- Détecte que tous ont validé
- Change statut à "validee"
- Envoie email de confirmation à Marie

**Administrateur** :
- Consulte la campagne validée
- Change le statut à "publiee"
- La campagne apparaît dans le calendrier aux dates prévues

### Cas 2 : Un validateur refuse une campagne

**Scénario** :
1. Campagne soumise avec 3 validateurs : A, B, C
2. Validateur A valide
3. Validateur B refuse avec motif : "Visuel non conforme à la charte graphique"

**Système** :
- Détecte le refus
- Change immédiatement le statut à "refusee" (même si C n'a pas encore répondu)
- Envoie email au créateur avec le motif du refus

**Créateur** :
- Consulte la campagne, lit le commentaire de B
- Modifie le visuel
- Re-soumet pour validation

**Système** :
- Supprime les anciennes validations (celle de A)
- Génère de nouveaux tokens
- Renvoie emails à A, B et C

**Note** : A doit valider à nouveau, même s'il avait déjà validé.

### Cas 3 : Utilisation du mode Passerelle

**Contexte** : Agora est hébergé sur l'intranet de la mairie, les validateurs sont externes.

**Configuration** :
```php
// config/app.php
'validation' => [
    'mode' => 'passerelle',
    'passerelle_url' => 'https://validation-agora.mairie.fr',
    'passerelle_api_key' => 'SECRET_KEY',
    'token_expiry_days' => 30,
]
```

**Scénario** :
1. Campagne créée et soumise pour validation
2. Email envoyé au validateur avec lien : `https://validation-agora.mairie.fr/validate/{token}`
3. Validateur (externe, sur Internet) clique sur le lien
4. Arrive sur la passerelle (serveur public)
5. Voit les informations de la campagne
6. Valide → réponse stockée dans SQLite sur la passerelle

**CRON (toutes les 5 minutes sur le serveur Agora intranet)** :
1. Exécute `php bin/sync-validations.php`
2. Appelle l'API de la passerelle : `GET https://validation-agora.mairie.fr/api.php?action=pending-validations`
3. Récupère la validation
4. L'enregistre dans Agora
5. Confirme à la passerelle : `POST /api.php?action=sync-completed`
6. Passerelle marque la validation comme synchronisée

**Résultat** : La validation externe est intégrée dans Agora avec un délai de 5 minutes maximum.

---

## Évolutions Futures

### Fonctionnalités Envisagées

1. **Workflow de Validation Avancé** :
   - Validation séquentielle (ordre imposé)
   - Validation conditionnelle (selon budget, type d'événement)
   - Délégation de validation

2. **Tableau de Bord Analytique** :
   - Statistiques sur les campagnes (nombre par statut, par mois)
   - Temps moyen de validation
   - Taux d'acceptation/refus
   - Graphiques (Chart.js)

3. **Notifications Temps Réel** :
   - WebSocket ou Server-Sent Events
   - Notifications navigateur (Push API)
   - Centre de notifications dans l'application

4. **Intégration Réseaux Sociaux** :
   - Publication automatique sur Facebook, Twitter
   - Planification via API (Buffer, Hootsuite)

5. **Gestion de Budget** :
   - Budget par campagne
   - Suivi des dépenses (impression, diffusion)
   - Reporting financier

6. **Commentaires et Collaboration** :
   - Fils de discussion sur chaque campagne
   - Mentions (@utilisateur)
   - Notifications en temps réel

7. **Versioning Avancé** :
   - Historique complet des modifications
   - Diff visuel
   - Restauration de versions

8. **Templates de Campagnes** :
   - Modèles pré-configurés
   - Duplication avancée
   - Bibliothèque de visuels

9. **API RESTful Complète** :
   - Endpoints pour toutes les entités
   - Documentation OpenAPI/Swagger
   - Authentification OAuth2

10. **Application Mobile** :
    - Consultation des campagnes
    - Validation mobile
    - Notifications push
    - Upload de photos

---

## Glossaire

- **Campagne** : Opération de communication pour promouvoir un événement ou une information
- **Support** : Canal de diffusion (écran, panneau, réseau social, etc.)
- **Validateur** : Personne chargée d'approuver ou refuser une campagne
- **Token** : Chaîne unique et sécurisée permettant la validation par email
- **Passerelle** : Serveur externe permettant la validation lorsque Agora est sur intranet
- **CRON** : Tâche planifiée exécutée automatiquement à intervalle régulier
- **Brouillon** : Campagne non soumise, en cours de création
- **Archivée** : Campagne terminée ou mise de côté, cachée par défaut
- **RBAC** : Role-Based Access Control, gestion des permissions par rôles

---

## Support et Documentation

- **Documentation technique** : Voir `/docs/`
- **Documentation passerelle** : [passerelle.md](passerelle.md)
- **Workflow technique** : [workflow.md](workflow.md)
- **Base de données** : Schéma dans `/database/migration/dump.sql`

---

**Version du document** : 1.0
**Date** : 19 octobre 2025
**Auteur** : Documentation générée automatiquement
