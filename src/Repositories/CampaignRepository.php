<?php

namespace Agora\Repositories;

use Agora\Services\Database;
use Agora\Models\Campaign;

class CampaignRepository
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM campaigns WHERE 1=1";
        $params = [];

        if (!empty($filters['statut'])) {
            $sql .= " AND statut = :statut";
            $params['statut'] = $filters['statut'];
        }

        $sql .= " ORDER BY created_at DESC";

        $data = $this->db->fetchAll($sql, $params);

        return array_map(function($row) {
            return new Campaign($row);
        }, $data);
    }

    public function findById(int $id): ?Campaign
    {
        $sql = "SELECT * FROM campaigns WHERE id = :id";
        $data = $this->db->fetch($sql, ['id' => $id]);

        return $data ? new Campaign($data) : null;
    }

    public function findAllWithDetails(array $filters = []): array
    {
        $sql = "SELECT c.* FROM campaigns c WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (c.titre LIKE :search1 OR c.demandeur LIKE :search2)";
            $params['search1'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND c.statut = :statut";
            $params['statut'] = $filters['statut'];
        } else {
            // Exclure les campagnes archivées par défaut
            $sql .= " AND c.statut != 'archivee'";
        }

        $sql .= " ORDER BY c.created_at DESC";

        $campaigns = $this->db->fetchAll($sql, $params);

        foreach ($campaigns as &$campaign) {
            $sqlSupports = "SELECT s.nom FROM campaign_supports cs
                            JOIN supports s ON cs.support_id = s.id
                            WHERE cs.campaign_id = :campaign_id";
            $supports = $this->db->fetchAll($sqlSupports, ['campaign_id' => $campaign['id']]);
            $campaign['supports'] = array_map(function($s) { return $s['nom']; }, $supports);

            // Récupérer le premier visuel (image uniquement)
            $sqlVisuel = "SELECT chemin, nom_original, type_mime
                          FROM files
                          WHERE campaign_id = :campaign_id
                          AND type_mime LIKE 'image/%'
                          ORDER BY uploaded_at ASC
                          LIMIT 1";
            $visuel = $this->db->fetch($sqlVisuel, ['campaign_id' => $campaign['id']]);
            $campaign['visuel'] = $visuel ?: null;
        }

        return $campaigns;
    }

    public function getStats(): array
    {
        $stats = [];

        // Campagnes en attente de validation
        $sql = "SELECT COUNT(*) as count FROM campaigns WHERE statut = 'brouillon'";
        $stats['en_attente'] = $this->db->fetch($sql)['count'] ?? 0;

        // Campagnes à valider
        $sql = "SELECT COUNT(*) as count FROM campaigns WHERE statut = 'en_validation'";
        $stats['a_valider'] = $this->db->fetch($sql)['count'] ?? 0;

        // Deadline < 3 jours
        $sql = "SELECT COUNT(*) as count FROM campaigns
                WHERE statut IN ('en_validation', 'validee')
                AND date_event_debut <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                AND date_event_debut >= CURDATE()";
        $stats['deadline_3j'] = $this->db->fetch($sql)['count'] ?? 0;

        // En retard
        $sql = "SELECT COUNT(*) as count FROM campaigns
                WHERE statut IN ('brouillon', 'en_validation')
                AND date_event_debut < CURDATE()";
        $stats['en_retard'] = $this->db->fetch($sql)['count'] ?? 0;

        return $stats;
    }

    public function getRecentCampaigns(int $limit = 5, string $filter = 'all'): array
    {
        $sql = "SELECT c.*,
                DATEDIFF(c.date_event_debut, CURDATE()) as days_left
                FROM campaigns c
                WHERE c.statut IN ('en_validation', 'validee', 'brouillon', 'publiee')";

        // Appliquer les filtres
        if ($filter === 'urgentes') {
            $sql .= " AND c.date_event_debut <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND c.date_event_debut >= CURDATE()";
        } elseif ($filter === 'en_validation') {
            $sql .= " AND c.statut = 'en_validation'";
        } elseif ($filter === 'validee') {
            $sql .= " AND c.statut = 'validee'";
        }

        $sql .= " ORDER BY c.created_at DESC LIMIT :limit";

        $campaigns = $this->db->fetchAll($sql, ['limit' => $limit]);

        // Enrichir avec les validateurs et supports
        foreach ($campaigns as &$campaign) {
            // Récupérer les validateurs
            $sqlValidateurs = "SELECT u.nom, u.prenom, v.action as statut
                               FROM campaign_validators cv
                               LEFT JOIN users u ON cv.user_id = u.id
                               LEFT JOIN validations v ON v.campaign_id = cv.campaign_id AND v.user_id = cv.user_id
                               WHERE cv.campaign_id = :campaign_id";
            $validateurs = $this->db->fetchAll($sqlValidateurs, ['campaign_id' => $campaign['id']]);

            // Formater les validateurs
            $campaign['validateurs'] = array_map(function($v) {
                return [
                    'nom' => ($v['prenom'] ?? '') . ' ' . ($v['nom'] ?? ''),
                    'statut' => $v['statut'] === 'valide' ? 'validé' : 'en_attente'
                ];
            }, $validateurs);

            // Récupérer les supports
            $sqlSupports = "SELECT s.nom
                            FROM campaign_supports cs
                            JOIN supports s ON cs.support_id = s.id
                            WHERE cs.campaign_id = :campaign_id";
            $supports = $this->db->fetchAll($sqlSupports, ['campaign_id' => $campaign['id']]);
            $campaign['supports'] = array_map(function($s) {
                return $s['nom'];
            }, $supports);

            // Récupérer le premier visuel (image uniquement)
            $sqlVisuel = "SELECT chemin, nom_original, type_mime
                          FROM files
                          WHERE campaign_id = :campaign_id
                          AND type_mime LIKE 'image/%'
                          ORDER BY uploaded_at ASC
                          LIMIT 1";
            $visuel = $this->db->fetch($sqlVisuel, ['campaign_id' => $campaign['id']]);
            $campaign['visuel'] = $visuel ?: null;

            // Le demandeur est déjà dans la colonne 'demandeur'
            // On le laisse tel quel
        }

        return $campaigns;
    }

    public function create(Campaign $campaign): int
    {
        $data = $campaign->toArray();
        unset($data['id']); // Retirer l'ID pour l'insertion

        return $this->db->insert('campaigns', $data);
    }

    public function update(Campaign $campaign): bool
    {
        $data = $campaign->toArray();
        $id = $data['id'];
        unset($data['id']);

        $affected = $this->db->update('campaigns', $data, 'id = :id', ['id' => $id]);
        return $affected > 0;
    }

    public function delete(int $id): bool
    {
        $affected = $this->db->delete('campaigns', 'id = :id', ['id' => $id]);
        return $affected > 0;
    }

    public function getTimelineCampaigns(int $days = 30): array
    {
        $sql = "SELECT c.id, c.titre as nom, c.statut, c.date_event_debut, c.date_event_fin,
                DATEDIFF(c.date_event_debut, CURDATE()) as start,
                DATEDIFF(c.date_event_fin, c.date_event_debut) as width
                FROM campaigns c
                WHERE c.date_event_debut >= CURDATE()
                AND c.date_event_debut <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND c.statut != 'archivee'
                ORDER BY c.date_event_debut ASC
                LIMIT 5";

        $campaigns = $this->db->fetchAll($sql, ['days' => $days]);

        // Calculer start et width pour l'affichage
        foreach ($campaigns as &$campaign) {
            $campaign['start'] = max(0, (int)$campaign['start']);
            $campaign['width'] = max(1, min(30 - $campaign['start'], (int)$campaign['width']));
        }

        return $campaigns;
    }
}
