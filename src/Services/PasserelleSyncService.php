<?php

namespace Agora\Services;

use Agora\Services\Database;
use Agora\Services\CampaignLogService;

class PasserelleSyncService
{
    private $db;
    private $logService;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logService = new CampaignLogService($db);
    }

    /**
     * Synchronise les validations depuis la passerelle
     * Retourne le nombre de validations synchronisées
     */
    public function synchronize(): array
    {
        // Charger la config depuis la base de données
        $mode = config('validation.mode', 'direct');

        // Ne rien faire si on n'est pas en mode passerelle
        if ($mode !== 'passerelle') {
            return [
                'success' => false,
                'message' => 'Mode passerelle désactivé',
                'synced' => 0
            ];
        }

        $passerelleUrl = config('validation.url', '');
        $apiKey = config('validation.apikey', '');

        if (empty($passerelleUrl) || empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Configuration passerelle incomplète',
                'synced' => 0
            ];
        }

        try {
            // 1. Récupérer les validations en attente depuis la passerelle
            $pendingValidations = $this->fetchPendingValidations($passerelleUrl, $apiKey);

            if ($pendingValidations === false) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la connexion à la passerelle',
                    'synced' => 0
                ];
            }

            if (empty($pendingValidations)) {
                return [
                    'success' => true,
                    'message' => 'Aucune validation en attente',
                    'synced' => 0
                ];
            }

            // 2. Enrichir les validations avec les données du token
            $enrichedValidations = $this->enrichValidations($pendingValidations);

            // 3. Traiter chaque validation
            $syncedIds = [];
            $errors = [];

            foreach ($enrichedValidations as $validation) {
                try {
                    if ($this->processValidation($validation)) {
                        $syncedIds[] = $validation['id'];
                    } else {
                        $errors[] = "Validation ID {$validation['id']}: Token invalide ou expiré";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Validation ID {$validation['id']}: " . $e->getMessage();
                }
            }

            // 4. Confirmer à la passerelle que les validations ont été synchronisées
            if (!empty($syncedIds)) {
                $this->confirmSync($passerelleUrl, $apiKey, $syncedIds);
            }

            return [
                'success' => true,
                'message' => count($syncedIds) . ' validation(s) synchronisée(s)',
                'synced' => count($syncedIds),
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'synced' => 0
            ];
        }
    }

    /**
     * Récupère les validations en attente depuis la passerelle
     * @return array|false
     */
    private function fetchPendingValidations(string $baseUrl, string $apiKey)
    {
        $url = rtrim($baseUrl, '/') . '/api.php/pending-validations';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return false;
        }

        $data = json_decode($response, true);

        return $data['success'] ?? false ? ($data['data'] ?? []) : false;
    }

    /**
     * Enrichit les validations avec les données du token (campaign_id, user_id)
     */
    private function enrichValidations(array $validations): array
    {
        $enriched = [];

        foreach ($validations as $validation) {
            $token = $validation['token'];

            // Récupérer les infos du token depuis la base Agora
            $tokenData = $this->db->fetch(
                "SELECT campaign_id, user_id, expires_at FROM validation_tokens WHERE token = ?",
                [$token]
            );

            if ($tokenData) {
                $validation['campaign_id'] = $tokenData['campaign_id'];
                $validation['user_id'] = $tokenData['user_id'];
                $validation['token_expires_at'] = $tokenData['expires_at'];
                $enriched[] = $validation;
            }
        }

        return $enriched;
    }

    /**
     * Traite une validation individuelle
     */
    private function processValidation(array $validation): bool
    {
        $token = $validation['token'];
        $campaignId = $validation['campaign_id'];
        $userId = $validation['user_id'];
        $action = $validation['action'];
        $commentaire = $validation['commentaire'] ?? null;

        // Vérifier si le token est toujours valide
        $tokenData = $this->db->fetch(
            "SELECT * FROM validation_tokens WHERE token = ? AND used_at IS NULL",
            [$token]
        );

        if (!$tokenData) {
            return false; // Token déjà utilisé ou invalide
        }

        // Vérifier l'expiration
        if (strtotime($tokenData['expires_at']) < time()) {
            return false; // Token expiré
        }

        // Récupérer les informations du validateur
        $validator = $this->db->fetch(
            "SELECT nom, prenom FROM users WHERE id = ?",
            [$userId]
        );

        $validatorName = $validator ? $validator['prenom'] . ' ' . $validator['nom'] : 'Validateur inconnu';

        // Enregistrer la validation
        $this->db->insert('validations', [
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'action' => $action,
            'commentaire' => $commentaire,
            'validated_at' => date('Y-m-d H:i:s'),
        ]);

        // Marquer le token comme utilisé
        $this->db->update('validation_tokens', [
            'used_at' => date('Y-m-d H:i:s'),
        ], 'token = :token_where', ['token_where' => $token]);

        // Logger l'événement (en indiquant que c'est synchronisé depuis la passerelle)
        $this->logService->logSynced($campaignId, $userId, $validatorName, $action);

        // Vérifier si tous les validateurs ont répondu
        $this->checkCampaignValidationStatus($campaignId);

        return true;
    }

    /**
     * Vérifie le statut de validation d'une campagne et met à jour si nécessaire
     */
    private function checkCampaignValidationStatus(int $campaignId): void
    {
        // Récupérer le nombre total de validateurs
        $totalValidators = $this->db->fetch(
            "SELECT COUNT(*) as count FROM campaign_validators WHERE campaign_id = ?",
            [$campaignId]
        );

        // Récupérer le nombre de validations effectuées
        $completedValidations = $this->db->fetch(
            "SELECT COUNT(*) as count FROM validations WHERE campaign_id = ?",
            [$campaignId]
        );

        // Vérifier s'il y a des refus
        $rejections = $this->db->fetch(
            "SELECT COUNT(*) as count FROM validations WHERE campaign_id = ? AND action = 'refuse'",
            [$campaignId]
        );

        // Logique de mise à jour du statut
        if ($rejections['count'] > 0) {
            // Au moins un refus -> statut "refusee"
            $this->db->update('campaigns', [
                'statut' => 'refusee'
            ], 'id = ?', [$campaignId]);

        } elseif ($completedValidations['count'] >= $totalValidators['count']) {
            // Tous ont validé -> statut "validee"
            $this->db->update('campaigns', [
                'statut' => 'validee'
            ], 'id = ?', [$campaignId]);
        }
    }

    /**
     * Confirme à la passerelle que les validations ont été synchronisées
     */
    private function confirmSync(string $baseUrl, string $apiKey, array $ids): bool
    {
        $url = rtrim($baseUrl, '/') . '/api.php/sync-completed';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['ids' => $ids]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}
