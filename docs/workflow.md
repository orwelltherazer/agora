# Workflow de Publication d'une Publicité

## 1. Idée / Concept
- **État :** `idée` ou `concept`
- **Description :** La publicité est simplement suggérée, sans création.
- **Actions possibles :**
  - Valider l'idée → passe à `en création`
  - Rejeter → passe à `abandonné`
  - Mettre en attente → reste à `idée`

---

## 2. Création
- **État :** `en création`
- **Description :** Le contenu est en cours de conception (texte, visuels, maquettes).
- **Actions possibles :**
  - Envoyer le BAT → passe à `en validation`
  - Ajouter des ressources (images, textes, fichiers)
  - Mettre en attente → reste à `en création`

---

## 3. BAT envoyé / Validation externe
- **État :** `en validation` ou `BAT envoyé`
- **Description :** Le bon à tirer est envoyé aux intervenants pour retour.
- **Actions possibles :**
  - Validation → passe à `BAT validé`
  - Refus → revient à `en création`
  - Mise en attente → reste à `en validation`

---

## 4. BAT validé
- **État :** `BAT validé` ou `approuvé`
- **Description :** Tous les intervenants ont donné leur accord.
- **Actions possibles :**
  - Préparer la diffusion → passe à `programmé`
  - Modifier contenu → revient à `en création`

---

## 5. Programmation / Planification
- **État :** `programmé`
- **Description :** La publicité est prête et planifiée pour diffusion.
- **Actions possibles :**
  - Modifier la date de diffusion → reste à `programmé`
  - Annuler ou replanifier → passe à `abandonné`
  - Lancer diffusion → passe à `publié`

---

## 6. Diffusion / Publication
- **État :** `publié` ou `diffusé`
- **Description :** La publicité est visible par le public cible.
- **Actions possibles :**
  - Suivi des performances
  - Commentaires post-diffusion
  - Archiver → passe à `archivé`

---

## 7. Archivage
- **État :** `archivé`
- **Description :** La publicité n’est plus active et conservée pour consultation ou audit.
- **Actions possibles :**
  - Restaurer → passe à `programmé` ou `BAT validé`
  - Consultation historique

---

## 8. États spéciaux / Exceptionnels
- **`refusé`** : BAT rejeté par un intervenant et non validé → revient à `en création`
- **`abandonné`** : La publicité est annulée avant diffusion
- **`en attente`** : La publicité est suspendue (ex. attente de ressources, délai interne)
