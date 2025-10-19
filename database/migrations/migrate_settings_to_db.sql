-- Migration des paramètres de app.php vers la base de données
-- Ajout d'une colonne catégorie pour organiser les paramètres

-- Ajouter la colonne catégorie si elle n'existe pas
ALTER TABLE `settings`
ADD COLUMN IF NOT EXISTS `categorie` VARCHAR(50) DEFAULT 'general' AFTER `cle`,
ADD COLUMN IF NOT EXISTS `ordre` INT DEFAULT 0 AFTER `categorie`;

-- Vider la table pour la repeupler avec les nouvelles données
TRUNCATE TABLE `settings`;

-- ============================================
-- CATÉGORIE : APPLICATION
-- ============================================

INSERT INTO `settings` (`cle`, `categorie`, `ordre`, `valeur`, `type`, `description`) VALUES
('app_name', 'application', 1, 'Agora', 'string', 'Nom de l''application'),
('app_description', 'application', 2, 'Gestion des communications', 'string', 'Description de l''application'),
('app_version', 'application', 3, '1.0.0', 'string', 'Version de l''application (lecture seule)'),
('app_url', 'application', 4, 'http://localhost/agora/public', 'string', 'URL de base de l''application'),
('app_timezone', 'application', 5, 'Europe/Paris', 'string', 'Fuseau horaire (ex: Europe/Paris, America/New_York)'),
('app_locale', 'application', 6, 'fr_FR', 'string', 'Langue (ex: fr_FR, en_US)'),
('app_debug', 'application', 7, '1', 'boolean', 'Mode debug (affiche les erreurs détaillées)');

-- ============================================
-- CATÉGORIE : SÉCURITÉ
-- ============================================

INSERT INTO `settings` (`cle`, `categorie`, `ordre`, `valeur`, `type`, `description`) VALUES
('session_name', 'securite', 1, 'AGORA_SESSION', 'string', 'Nom du cookie de session'),
('session_lifetime', 'securite', 2, '7200', 'integer', 'Durée de vie de la session (en secondes, 7200 = 2 heures)'),
('session_secure', 'securite', 3, '0', 'boolean', 'Cookies sécurisés (HTTPS uniquement) - Activer en production'),
('session_httponly', 'securite', 4, '1', 'boolean', 'Cookies HTTPOnly (protection XSS)'),
('session_samesite', 'securite', 5, 'Lax', 'string', 'Politique SameSite (Lax, Strict, None)');

-- ============================================
-- CATÉGORIE : FICHIERS
-- ============================================

INSERT INTO `settings` (`cle`, `categorie`, `ordre`, `valeur`, `type`, `description`) VALUES
('upload_max_size', 'fichiers', 1, '10485760', 'integer', 'Taille maximale des fichiers en octets (10485760 = 10 Mo)'),
('upload_allowed_types', 'fichiers', 2, 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,ai,psd', 'string', 'Extensions autorisées (séparées par des virgules)');

-- ============================================
-- CATÉGORIE : PAGINATION
-- ============================================

INSERT INTO `settings` (`cle`, `categorie`, `ordre`, `valeur`, `type`, `description`) VALUES
('pagination_per_page', 'pagination', 1, '20', 'integer', 'Nombre d''éléments par page dans les listes');

-- ============================================
-- CATÉGORIE : NOTIFICATIONS
-- ============================================

INSERT INTO `settings` (`cle`, `categorie`, `ordre`, `valeur`, `type`, `description`) VALUES
('notif_relance_delai_jours', 'notifications', 1, '5', 'integer', 'Délai avant relance des validateurs (en jours)'),
('notif_deadline_alerte_jours', 'notifications', 2, '7', 'integer', 'Alerte avant échéance (en jours)');

-- ============================================
-- CATÉGORIE : VALIDATION
-- ============================================

INSERT INTO `settings` (`cle`, `categorie`, `ordre`, `valeur`, `type`, `description`) VALUES
('validation_mode', 'validation', 1, 'direct', 'string', 'Mode de validation : "direct" (application sur internet) ou "passerelle" (application sur intranet)'),
('validation_url', 'validation', 2, '', 'string', 'URL de la passerelle (utilisée uniquement si mode = passerelle)'),
('validation_apikey', 'validation', 3, '', 'string', 'Clé API pour sécuriser les échanges avec la passerelle'),
('validation_tokendays', 'validation', 4, '30', 'integer', 'Durée de validité des tokens de validation (en jours)');

-- ============================================
-- CATÉGORIE : EMAIL (à configurer selon SMTP)
-- ============================================

INSERT INTO `settings` (`cle`, `categorie`, `ordre`, `valeur`, `type`, `description`) VALUES
('email_host', 'email', 1, '', 'string', 'Serveur SMTP (ex: smtp.gmail.com)'),
('email_port', 'email', 2, '587', 'integer', 'Port SMTP (587 pour TLS, 465 pour SSL, 25 non sécurisé)'),
('email_username', 'email', 3, '', 'string', 'Nom d''utilisateur SMTP'),
('email_password', 'email', 4, '', 'password', 'Mot de passe SMTP (stocké en clair - attention)'),
('email_encryption', 'email', 5, 'tls', 'string', 'Chiffrement (tls, ssl, ou vide)'),
('email_fromaddress', 'email', 6, 'noreply@example.com', 'string', 'Adresse email expéditeur'),
('email_fromname', 'email', 7, 'Agora', 'string', 'Nom de l''expéditeur');
