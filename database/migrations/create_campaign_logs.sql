-- Création de la table de logs pour tracer toutes les opérations sur les campagnes

CREATE TABLE IF NOT EXISTS `campaign_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'Type d''action: created, updated, status_changed, archived, unarchived, deleted',
  `description` text DEFAULT NULL COMMENT 'Description détaillée de l''action',
  `old_values` json DEFAULT NULL COMMENT 'Anciennes valeurs (pour les updates)',
  `new_values` json DEFAULT NULL COMMENT 'Nouvelles valeurs (pour les updates)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `campaign_logs_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index composite pour les recherches fréquentes
CREATE INDEX `idx_campaign_user` ON `campaign_logs` (`campaign_id`, `user_id`);
CREATE INDEX `idx_user_action` ON `campaign_logs` (`user_id`, `action`);
