-- Migration pour ajouter les dates de début et fin de campagne (publication)
-- Date: 2025-10-17

ALTER TABLE `campaigns`
ADD COLUMN `date_campagne_debut` DATE NULL COMMENT 'Date de début de la campagne (lancement)' AFTER `date_event_fin`,
ADD COLUMN `date_campagne_fin` DATE NULL COMMENT 'Date de fin de la campagne' AFTER `date_campagne_debut`;

-- Ajouter un index pour optimiser les recherches
ALTER TABLE `campaigns`
ADD INDEX `idx_campagne_dates` (`date_campagne_debut`, `date_campagne_fin`);
