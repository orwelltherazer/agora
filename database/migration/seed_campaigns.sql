-- Seed de campagnes de test pour Agora
-- Date: 2025-10-17

-- Vider la table campaigns
TRUNCATE TABLE `campaigns`;

-- Insérer des campagnes de test
INSERT INTO `campaigns` (`titre`, `description`, `demandeur`, `demandeur_email`, `date_event_debut`, `date_event_fin`, `date_campagne_debut`, `date_campagne_fin`, `statut`, `priorite`, `created_by`, `created_at`) VALUES

-- Campagnes urgentes
('Fermeture piscine municipale', 'Travaux urgents de maintenance, fermeture temporaire', 'Service Sports et Loisirs', 'sports@mairie.fr', '2025-10-20', '2025-10-22', '2025-10-18', '2025-10-22', 'en_validation', 'haute', 1, NOW()),

('Marché de Noël 2025', 'Organisation du traditionnel marché de Noël sur la place centrale', 'Office du Tourisme', 'tourisme@mairie.fr', '2025-12-15', '2025-12-24', '2025-11-20', '2025-12-24', 'en_validation', 'haute', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Campagnes en validation
('Concert Jazz Club', 'Soirée jazz avec le groupe local "Les Swingueurs"', 'Association Jazz Club', 'contact@jazzclub.fr', '2025-11-10', '2025-11-10', '2025-10-25', '2025-11-10', 'en_validation', 'normale', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),

('Forum des associations', 'Rencontre annuelle de toutes les associations de la ville', 'Service Vie Associative', 'asso@mairie.fr', '2025-11-05', '2025-11-05', '2025-10-22', '2025-11-05', 'validee', 'haute', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),

('Collecte déchets verts', 'Collecte spéciale des déchets verts pour l\'automne', 'Service Environnement', 'environnement@mairie.fr', '2025-10-25', '2025-10-27', '2025-10-15', '2025-10-27', 'publiee', 'normale', 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),

-- Campagnes validées
('Semaine du développement durable', 'Actions et ateliers autour du développement durable', 'Service Environnement', 'environnement@mairie.fr', '2025-11-20', '2025-11-27', '2025-11-01', '2025-11-27', 'validee', 'normale', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),

('Spectacle de Noël pour enfants', 'Spectacle gratuit offert aux enfants de la commune', 'Service Culturel', 'culture@mairie.fr', '2025-12-18', '2025-12-18', '2025-11-25', '2025-12-18', 'en_validation', 'normale', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Campagnes planifiées
('Vœux du Maire 2026', 'Cérémonie des vœux du Maire aux habitants', 'Cabinet du Maire', 'cabinet@mairie.fr', '2026-01-15', '2026-01-15', '2026-01-02', '2026-01-15', 'brouillon', 'haute', 1, DATE_SUB(NOW(), INTERVAL 1 HOUR)),

('Journée citoyenne de nettoyage', 'Grande journée de nettoyage des espaces publics', 'Service Cadre de Vie', 'cadredevie@mairie.fr', '2025-11-12', '2025-11-12', '2025-10-28', '2025-11-12', 'brouillon', 'normale', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),

('Carnaval de printemps', 'Défilé et animations pour le carnaval annuel', 'Comité des Fêtes', 'fetes@mairie.fr', '2026-03-21', '2026-03-22', '2026-02-15', '2026-03-22', 'brouillon', 'normale', 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),

-- Campagne publiée
('Inscription scolaire 2026', 'Période d\'inscription pour la rentrée scolaire 2026', 'Service Scolaire', 'scolaire@mairie.fr', '2026-01-10', '2026-02-28', '2025-12-15', '2026-02-28', 'publiee', 'haute', 1, DATE_SUB(NOW(), INTERVAL 2 WEEK)),

-- Campagne en retard
('Réunion publique budget', 'Présentation du budget municipal aux citoyens', 'Direction Finances', 'finances@mairie.fr', '2025-10-18', '2025-10-18', '2025-10-10', '2025-10-18', 'en_validation', 'haute', 1, DATE_SUB(NOW(), INTERVAL 8 DAY));
