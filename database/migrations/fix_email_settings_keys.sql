-- Migration: Correction des clés de configuration email
-- Les clés avec plusieurs underscores causent des problèmes avec le ConfigService

-- Renommer les clés pour éviter les multiples underscores
UPDATE `settings` SET `cle` = 'email_host' WHERE `cle` = 'email_smtp_host';
UPDATE `settings` SET `cle` = 'email_port' WHERE `cle` = 'email_smtp_port';
UPDATE `settings` SET `cle` = 'email_username' WHERE `cle` = 'email_smtp_username';
UPDATE `settings` SET `cle` = 'email_password' WHERE `cle` = 'email_smtp_password';
UPDATE `settings` SET `cle` = 'email_encryption' WHERE `cle` = 'email_smtp_encryption';
UPDATE `settings` SET `cle` = 'email_fromaddress' WHERE `cle` = 'email_from_address';
UPDATE `settings` SET `cle` = 'email_fromname' WHERE `cle` = 'email_from_name';

-- Mettre à jour les descriptions
UPDATE `settings` SET `description` = 'Serveur SMTP pour l\'envoi d\'emails'
WHERE `cle` = 'email_host';

UPDATE `settings` SET `description` = 'Port du serveur SMTP (25, 465, 587)'
WHERE `cle` = 'email_port';

UPDATE `settings` SET `description` = 'Nom d\'utilisateur SMTP'
WHERE `cle` = 'email_username';

UPDATE `settings` SET `description` = 'Mot de passe SMTP'
WHERE `cle` = 'email_password';

UPDATE `settings` SET `description` = 'Type de chiffrement (tls, ssl, ou vide)'
WHERE `cle` = 'email_encryption';

UPDATE `settings` SET `description` = 'Adresse email d\'expédition'
WHERE `cle` = 'email_fromaddress';

UPDATE `settings` SET `description` = 'Nom de l\'expéditeur'
WHERE `cle` = 'email_fromname';
