<?php

namespace Agora\Services;

use Agora\Services\Database;

class CampaignLogService
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Enregistre une action sur une campagne
     */
    public function log(int $campaignId, int $userId, string $action, string $description = null, array $oldValues = null, array $newValues = null): void
    {
        $this->db->insert('campaign_logs', [
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
        ]);
    }

    /**
     * Log pour la création d'une campagne
     */
    public function logCreated(int $campaignId, int $userId, string $titre): void
    {
        $this->log(
            $campaignId,
            $userId,
            'created',
            "Création de la campagne '{$titre}'"
        );
    }

    /**
     * Log pour la mise à jour d'une campagne
     */
    public function logUpdated(int $campaignId, int $userId, array $oldValues, array $newValues): void
    {
        $changes = [];
        foreach ($newValues as $key => $value) {
            if (isset($oldValues[$key]) && $oldValues[$key] != $value) {
                $changes[] = $key;
            }
        }

        $description = "Modification de la campagne";
        if (!empty($changes)) {
            $description .= " (" . implode(', ', $changes) . ")";
        }

        $this->log(
            $campaignId,
            $userId,
            'updated',
            $description,
            $oldValues,
            $newValues
        );
    }

    /**
     * Log pour le changement de statut
     */
    public function logStatusChanged(int $campaignId, int $userId, string $oldStatus, string $newStatus): void
    {
        $statusLabels = [
            'brouillon' => 'Brouillon',
            'en_validation' => 'En validation',
            'validee' => 'Validée',
            'publiee' => 'Publiée',
            'archivee' => 'Archivée',
            'refusee' => 'Refusée',
            'annulee' => 'Annulée',
        ];

        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        $this->log(
            $campaignId,
            $userId,
            'status_changed',
            "Changement de statut : {$oldLabel} → {$newLabel}",
            ['statut' => $oldStatus],
            ['statut' => $newStatus]
        );
    }

    /**
     * Log pour l'archivage
     */
    public function logArchived(int $campaignId, int $userId): void
    {
        $this->log(
            $campaignId,
            $userId,
            'archived',
            "Campagne archivée"
        );
    }

    /**
     * Log pour le désarchivage
     */
    public function logUnarchived(int $campaignId, int $userId): void
    {
        $this->log(
            $campaignId,
            $userId,
            'unarchived',
            "Campagne désarchivée"
        );
    }

    /**
     * Log pour la suppression d'un fichier
     */
    public function logFileDeleted(int $campaignId, int $userId, string $filename): void
    {
        $this->log(
            $campaignId,
            $userId,
            'file_deleted',
            "Suppression du fichier '{$filename}'"
        );
    }

    /**
     * Log pour l'ajout de fichiers
     */
    public function logFilesAdded(int $campaignId, int $userId, int $count): void
    {
        $this->log(
            $campaignId,
            $userId,
            'files_added',
            "Ajout de {$count} fichier(s)"
        );
    }

    /**
     * Log pour la validation
     */
    public function logValidated(int $campaignId, int $userId, string $validatorName): void
    {
        $this->log(
            $campaignId,
            $userId,
            'validated',
            "Validation par {$validatorName}"
        );
    }

    /**
     * Log pour le refus
     */
    public function logRejected(int $campaignId, int $userId, string $validatorName, string $reason = null): void
    {
        $description = "Refus par {$validatorName}";
        if ($reason) {
            $description .= " : {$reason}";
        }

        $this->log(
            $campaignId,
            $userId,
            'rejected',
            $description
        );
    }

    /**
     * Log pour la synchronisation depuis la passerelle
     */
    public function logSynced(int $campaignId, int $userId, string $validatorName, string $action): void
    {
        $actionLabel = $action === 'valide' ? 'Validation' : 'Refus';

        $this->log(
            $campaignId,
            $userId,
            $action === 'valide' ? 'validated' : 'rejected',
            "{$actionLabel} par {$validatorName} (synchronisé depuis la passerelle)"
        );
    }

    /**
     * Récupère les logs avec filtres
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT
                    cl.*,
                    c.titre as campaign_titre,
                    u.nom as user_nom,
                    u.prenom as user_prenom
                FROM campaign_logs cl
                JOIN campaigns c ON cl.campaign_id = c.id
                JOIN users u ON cl.user_id = u.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['campaign_id'])) {
            $sql .= " AND cl.campaign_id = :campaign_id";
            $params['campaign_id'] = $filters['campaign_id'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND cl.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND cl.action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.titre LIKE :search OR cl.description LIKE :search2)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY cl.created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Compte le nombre total de logs
     */
    public function countLogs(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count
                FROM campaign_logs cl
                JOIN campaigns c ON cl.campaign_id = c.id
                JOIN users u ON cl.user_id = u.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['campaign_id'])) {
            $sql .= " AND cl.campaign_id = :campaign_id";
            $params['campaign_id'] = $filters['campaign_id'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND cl.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND cl.action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.titre LIKE :search OR cl.description LIKE :search2)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        $result = $this->db->fetch($sql, $params);
        return (int)($result['count'] ?? 0);
    }
}
