<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Agora\Services\ValidationTokenService;
use Twig\Environment;

class PublicValidationController
{
    private $db;
    private $twig;
    private $tokenService;

    public function __construct(Database $db, Environment $twig)
    {
        $this->db = $db;
        $this->twig = $twig;
        $this->tokenService = new ValidationTokenService($db);
    }

    /**
     * Affiche la page de validation publique avec token
     */
    public function show(string $token): void
    {
        // Valider le token
        $validationData = $this->tokenService->validateToken($token);

        if (!$validationData) {
            http_response_code(404);
            echo $this->twig->render('errors/404.twig', [
                'message' => 'Ce lien de validation est invalide ou a expiré.'
            ]);
            return;
        }

        // Récupérer les fichiers de la campagne
        $files = $this->db->fetchAll(
            "SELECT * FROM files
             WHERE campaign_id = :campaign_id
             AND est_version_actuelle = 1
             ORDER BY uploaded_at DESC",
            ['campaign_id' => $validationData['campaign_id']]
        );

        // Récupérer les supports
        $supports = $this->db->fetchAll(
            "SELECT s.* FROM supports s
             JOIN campaign_supports cs ON s.id = cs.support_id
             WHERE cs.campaign_id = :campaign_id",
            ['campaign_id' => $validationData['campaign_id']]
        );

        // Récupérer les validateurs et leur statut
        $validateurs = $this->db->fetchAll(
            "SELECT u.*, v.action as validation_statut
             FROM campaign_validators cv
             JOIN users u ON cv.user_id = u.id
             LEFT JOIN validations v ON v.campaign_id = cv.campaign_id AND v.user_id = cv.user_id
             WHERE cv.campaign_id = :campaign_id
             ORDER BY cv.ordre",
            ['campaign_id' => $validationData['campaign_id']]
        );

        // Rendre la vue
        echo $this->twig->render('public/validation.twig', [
            'campaign' => $validationData,
            'token' => $token,
            'validator' => [
                'id' => $validationData['user_id'],
                'nom' => $validationData['nom'],
                'prenom' => $validationData['prenom'],
                'email' => $validationData['validator_email'],
            ],
            'files' => $files,
            'supports' => $supports,
            'validateurs' => $validateurs,
        ]);
    }

    /**
     * Traite la soumission de validation (valider ou refuser)
     */
    public function submit(string $token): void
    {
        // Valider le token
        $validationData = $this->tokenService->validateToken($token);

        if (!$validationData) {
            http_response_code(404);
            echo json_encode(['error' => 'Token invalide ou expiré']);
            return;
        }

        $action = $_POST['action'] ?? '';
        $commentaire = $_POST['commentaire'] ?? null;

        if (!in_array($action, ['valide', 'refuse'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Action invalide']);
            return;
        }

        // Enregistrer la validation
        $this->db->insert('validations', [
            'campaign_id' => $validationData['campaign_id'],
            'user_id' => $validationData['user_id'],
            'action' => $action,
            'commentaire' => $commentaire,
        ]);

        // Marquer le token comme utilisé
        $this->tokenService->markAsUsed(
            $token,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );

        // Mettre à jour le statut de la campagne si nécessaire
        if ($action === 'refuse') {
            $this->db->update('campaigns', [
                'statut' => 'refusee',
            ], 'id = :id', ['id' => $validationData['campaign_id']]);
        }

        // Rediriger vers la page de confirmation
        header('Location: /agora/public/validate/' . $token . '?success=1');
        exit;
    }
}
