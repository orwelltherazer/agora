<?php

namespace Agora\Services;

use Agora\Services\Database;

class ValidationTokenService
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Génère un token de validation sécurisé pour une campagne et un utilisateur
     *
     * @param int $campaignId ID de la campagne
     * @param int $userId ID de l'utilisateur validateur
     * @param int $expiryDays Nombre de jours avant expiration (défaut: 30)
     * @return string Le token généré
     */
    public function generateToken(int $campaignId, int $userId, int $expiryDays = 30): string
    {
        // Générer un token unique et sécurisé
        $token = bin2hex(random_bytes(32)); // 64 caractères hexadécimaux

        // Calculer la date d'expiration
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));

        // Vérifier si un token existe déjà pour cette combinaison campagne/utilisateur
        $existing = $this->db->fetch(
            "SELECT id FROM validation_tokens
             WHERE campaign_id = :campaign_id AND user_id = :user_id AND used_at IS NULL",
            ['campaign_id' => $campaignId, 'user_id' => $userId]
        );

        if ($existing) {
            // Mettre à jour le token existant
            $this->db->update('validation_tokens', [
                'token' => $token,
                'expires_at' => $expiresAt,
            ], 'id = :id', ['id' => $existing['id']]);
        } else {
            // Créer un nouveau token
            $this->db->insert('validation_tokens', [
                'campaign_id' => $campaignId,
                'user_id' => $userId,
                'token' => $token,
                'expires_at' => $expiresAt,
            ]);
        }

        return $token;
    }

    /**
     * Valide un token et retourne les informations associées
     *
     * @param string $token Le token à valider
     * @return array|null Informations de validation ou null si invalide
     */
    public function validateToken(string $token): ?array
    {
        $tokenData = $this->db->fetch(
            "SELECT vt.*, c.*, u.nom, u.prenom, u.email as validator_email
             FROM validation_tokens vt
             JOIN campaigns c ON vt.campaign_id = c.id
             JOIN users u ON vt.user_id = u.id
             WHERE vt.token = :token
             AND vt.expires_at > NOW()
             AND vt.used_at IS NULL",
            ['token' => $token]
        );

        return $tokenData ?: null;
    }

    /**
     * Marque un token comme utilisé
     *
     * @param string $token Le token à marquer
     * @param string|null $ipAddress Adresse IP du visiteur
     * @param string|null $userAgent User agent du navigateur
     * @return bool Succès de l'opération
     */
    public function markAsUsed(string $token, ?string $ipAddress = null, ?string $userAgent = null): bool
    {
        $updated = $this->db->update('validation_tokens', [
            'used_at' => date('Y-m-d H:i:s'),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], 'token = :token', ['token' => $token]);

        return $updated > 0;
    }

    /**
     * Nettoie les tokens expirés (à appeler via un cron quotidien)
     *
     * @param int $daysOld Nombre de jours pour considérer un token comme obsolète (défaut: 90)
     * @return int Nombre de tokens supprimés
     */
    public function cleanExpiredTokens(int $daysOld = 90): int
    {
        $deleted = $this->db->delete(
            'validation_tokens',
            'expires_at < DATE_SUB(NOW(), INTERVAL :days DAY)',
            ['days' => $daysOld]
        );

        return $deleted;
    }

    /**
     * Révoque tous les tokens d'une campagne
     *
     * @param int $campaignId ID de la campagne
     * @return int Nombre de tokens révoqués
     */
    public function revokeTokensByCampaign(int $campaignId): int
    {
        $deleted = $this->db->delete(
            'validation_tokens',
            'campaign_id = :campaign_id',
            ['campaign_id' => $campaignId]
        );

        return $deleted;
    }

    /**
     * Génère une URL publique de validation
     *
     * @param string $token Le token de validation
     * @param string $baseUrl URL de base de l'application
     * @return string L'URL complète de validation
     */
    public function generateValidationUrl(string $token, string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . '/public/validate/' . $token;
    }
}
