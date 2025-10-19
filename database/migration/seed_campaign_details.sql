-- Seed des supports et validateurs pour les campagnes de test
-- Date: 2025-10-17

-- Ajouter des supports aux campagnes
-- Campagne #1 - Fermeture piscine municipale
INSERT INTO `campaign_supports` (`campaign_id`, `support_id`, `date_pub_debut`, `date_pub_fin`) VALUES
(1, 1, '2025-10-18', '2025-10-22'),  -- Panneaux lumineux
(1, 3, '2025-10-18', '2025-10-22');  -- Facebook

-- Campagne #2 - Marché de Noël 2025
INSERT INTO `campaign_supports` (`campaign_id`, `support_id`, `date_pub_debut`, `date_pub_fin`) VALUES
(2, 2, '2025-11-20', '2025-12-24'),  -- 4x3
(2, 3, '2025-11-20', '2025-12-24'),  -- Facebook
(2, 4, '2025-11-20', '2025-12-24');  -- Site web

-- Campagne #3 - Concert Jazz Club
INSERT INTO `campaign_supports` (`campaign_id`, `support_id`, `date_pub_debut`, `date_pub_fin`) VALUES
(3, 5, '2025-10-25', '2025-11-10'),  -- Flyers
(3, 4, '2025-10-25', '2025-11-10');  -- Site web

-- Campagne #4 - Forum des associations
INSERT INTO `campaign_supports` (`campaign_id`, `support_id`, `date_pub_debut`, `date_pub_fin`) VALUES
(4, 2, '2025-10-22', '2025-11-05'),  -- 4x3
(4, 3, '2025-10-22', '2025-11-05'),  -- Facebook
(4, 5, '2025-10-22', '2025-11-05');  -- Flyers

-- Campagne #5 - Collecte déchets verts
INSERT INTO `campaign_supports` (`campaign_id`, `support_id`, `date_pub_debut`, `date_pub_fin`) VALUES
(5, 4, '2025-10-15', '2025-10-27'),  -- Site web
(5, 6, '2025-10-15', '2025-10-27');  -- Intramuros

-- Campagne #6 - Semaine du développement durable
INSERT INTO `campaign_supports` (`campaign_id`, `support_id`, `date_pub_debut`, `date_pub_fin`) VALUES
(6, 2, '2025-11-01', '2025-11-27'),  -- 4x3
(6, 3, '2025-11-01', '2025-11-27'),  -- Facebook
(6, 4, '2025-11-01', '2025-11-27');  -- Site web

-- Campagne #7 - Spectacle de Noël pour enfants
INSERT INTO `campaign_supports` (`campaign_id`, `support_id`, `date_pub_debut`, `date_pub_fin`) VALUES
(7, 5, '2025-11-25', '2025-12-18'),  -- Flyers
(7, 3, '2025-11-25', '2025-12-18');  -- Facebook

-- Campagne #12 - Réunion publique budget
INSERT INTO `campaign_supports` (`campaign_id`, `support_id`, `date_pub_debut`, `date_pub_fin`) VALUES
(12, 1, '2025-10-10', '2025-10-18'),  -- Panneaux lumineux
(12, 4, '2025-10-10', '2025-10-18');  -- Site web


-- Ajouter des validateurs aux campagnes
-- On suppose que les utilisateurs 1, 2, 3 existent (créés par le dump initial)
-- User 1 = Admin, User 2 = DGS, User 3 = Élu

-- Campagne #1 - Fermeture piscine municipale (urgente)
INSERT INTO `campaign_validators` (`campaign_id`, `user_id`, `ordre`) VALUES
(1, 1, 1);  -- Admin/Maire

-- Ajouter des validations
INSERT INTO `validations` (`campaign_id`, `user_id`, `action`, `commentaire`, `created_at`) VALUES
(1, 1, 'valide', 'Validation urgente approuvée', NOW());

-- Campagne #2 - Marché de Noël 2025
INSERT INTO `campaign_validators` (`campaign_id`, `user_id`, `ordre`) VALUES
(2, 2, 1),  -- DGS
(2, 1, 2),  -- Maire
(2, 3, 3);  -- Élu Culture

INSERT INTO `validations` (`campaign_id`, `user_id`, `action`, `commentaire`, `created_at`) VALUES
(2, 2, 'valide', 'Projet validé', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Campagne #3 - Concert Jazz Club
INSERT INTO `campaign_validators` (`campaign_id`, `user_id`, `ordre`) VALUES
(3, 2, 1),  -- DGS
(3, 3, 2);  -- Élu Culture

INSERT INTO `validations` (`campaign_id`, `user_id`, `action`, `commentaire`, `created_at`) VALUES
(3, 2, 'valide', 'OK pour moi', DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- Campagne #4 - Forum des associations (validée)
INSERT INTO `campaign_validators` (`campaign_id`, `user_id`, `ordre`) VALUES
(4, 2, 1),  -- DGS
(4, 1, 2);  -- Maire

INSERT INTO `validations` (`campaign_id`, `user_id`, `action`, `commentaire`, `created_at`) VALUES
(4, 2, 'valide', 'Validé', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 1, 'valide', 'Approuvé', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Campagne #7 - Spectacle de Noël pour enfants
INSERT INTO `campaign_validators` (`campaign_id`, `user_id`, `ordre`) VALUES
(7, 2, 1),  -- DGS
(7, 3, 2);  -- Élu Culture

-- Campagne #12 - Réunion publique budget (en retard)
INSERT INTO `campaign_validators` (`campaign_id`, `user_id`, `ordre`) VALUES
(12, 2, 1),  -- DGS
(12, 1, 2);  -- Maire

INSERT INTO `validations` (`campaign_id`, `user_id`, `action`, `commentaire`, `created_at`) VALUES
(12, 2, 'valide', 'Validé avec retard', DATE_SUB(NOW(), INTERVAL 5 DAY));
