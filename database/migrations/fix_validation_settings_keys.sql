-- Migration: Correction des clés de configuration de validation
-- Les clés avec plusieurs underscores causent des problèmes avec le ConfigService
-- qui utilise les underscores pour créer une hiérarchie

-- Renommer les clés pour éviter les multiples underscores
UPDATE `settings` SET `cle` = 'validation_url' WHERE `cle` = 'validation_passerelle_url';
UPDATE `settings` SET `cle` = 'validation_apikey' WHERE `cle` = 'validation_passerelle_api_key';
UPDATE `settings` SET `cle` = 'validation_token_days' WHERE `cle` = 'validation_token_expiry_days';

-- Mettre à jour les descriptions
UPDATE `settings` SET `description` = 'URL de la passerelle (utilisée uniquement si mode = passerelle)'
WHERE `cle` = 'validation_url';

UPDATE `settings` SET `description` = 'Clé API pour sécuriser les échanges avec la passerelle (IMPORTANT: à changer en production)'
WHERE `cle` = 'validation_apikey';

UPDATE `settings` SET `description` = 'Durée de validité des tokens de validation (en jours)'
WHERE `cle` = 'validation_token_days';
