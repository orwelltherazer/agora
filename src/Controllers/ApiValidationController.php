<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Agora\Services\ValidationTokenService;

/**
 * API publique pour la validation de campagnes
 * Accessible sans authentification mais avec token
 */
class ApiValidationController
{
    private $db;
    private $tokenService;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->tokenService = new ValidationTokenService($db);
    }

    /**
     * Récupère les données d'une campagne via son token de validation
     * GET /api/validate/{token}
     */
    public function getData(string $token): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); // Permet l'accès depuis n'importe quel domaine
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Content-Type');

        // Valider le token
        $validationData = $this->tokenService->validateToken($token);

        if (!$validationData) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Token invalide ou expiré',
                'message' => 'Ce lien de validation n\'est plus valide.'
            ]);
            return;
        }

        // Récupérer les fichiers
        $files = $this->db->fetchAll(
            "SELECT id, nom_original, type_mime, taille, chemin
             FROM files
             WHERE campaign_id = :campaign_id
             AND est_version_actuelle = 1
             ORDER BY uploaded_at DESC",
            ['campaign_id' => $validationData['campaign_id']]
        );

        // Convertir les chemins en URLs complètes
        $appConfig = require __DIR__ . '/../../config/app.php';
        foreach ($files as &$file) {
            $file['url'] = $appConfig['url'] . '/' . $file['chemin'];
            // Ajouter une URL de téléchargement via l'API pour éviter les problèmes d'accès
            $file['download_url'] = $appConfig['url'] . '/api/download/' . $token . '/' . $file['id'];
        }

        // Récupérer les supports
        $supports = $this->db->fetchAll(
            "SELECT s.nom, s.description
             FROM supports s
             JOIN campaign_supports cs ON s.id = cs.support_id
             WHERE cs.campaign_id = :campaign_id",
            ['campaign_id' => $validationData['campaign_id']]
        );

        // Récupérer les validateurs
        $validateurs = $this->db->fetchAll(
            "SELECT u.prenom, u.nom, v.action as statut
             FROM campaign_validators cv
             JOIN users u ON cv.user_id = u.id
             LEFT JOIN validations v ON v.campaign_id = cv.campaign_id AND v.user_id = cv.user_id
             WHERE cv.campaign_id = :campaign_id
             ORDER BY cv.ordre",
            ['campaign_id' => $validationData['campaign_id']]
        );

        // Construire la réponse
        $response = [
            'success' => true,
            'campaign' => [
                'id' => $validationData['campaign_id'],
                'titre' => $validationData['titre'],
                'description' => $validationData['description'],
                'demandeur' => $validationData['demandeur'],
                'demandeur_email' => $validationData['demandeur_email'],
                'date_event_debut' => $validationData['date_event_debut'],
                'date_event_fin' => $validationData['date_event_fin'],
                'date_campagne_debut' => $validationData['date_campagne_debut'],
                'date_campagne_fin' => $validationData['date_campagne_fin'],
                'priorite' => $validationData['priorite'],
                'statut' => $validationData['statut'],
            ],
            'validator' => [
                'prenom' => $validationData['prenom'],
                'nom' => $validationData['nom'],
                'email' => $validationData['validator_email'],
            ],
            'files' => $files,
            'supports' => $supports,
            'validateurs' => $validateurs,
            'token' => $token,
        ];

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Soumet une validation (valider ou refuser)
     * POST /api/validate/{token}
     */
    public function submitValidation(string $token): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Content-Type');

        // Valider le token
        $validationData = $this->tokenService->validateToken($token);

        if (!$validationData) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Token invalide ou expiré'
            ]);
            return;
        }

        // Récupérer les données POST (JSON ou form-data)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $action = $input['action'] ?? '';
        $commentaire = $input['commentaire'] ?? null;

        if (!in_array($action, ['valide', 'refuse'])) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Action invalide',
                'message' => 'L\'action doit être "valide" ou "refuse"'
            ]);
            return;
        }

        try {
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

            // Mettre à jour le statut de la campagne si refusée
            if ($action === 'refuse') {
                $this->db->update('campaigns', [
                    'statut' => 'refusee',
                ], 'id = :id', ['id' => $validationData['campaign_id']]);
            }

            echo json_encode([
                'success' => true,
                'message' => $action === 'valide'
                    ? 'Campagne validée avec succès'
                    : 'Campagne refusée',
                'action' => $action
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur lors de l\'enregistrement',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Télécharge un fichier via le token (pour éviter les problèmes d'accès direct)
     * GET /api/download/{token}/{fileId}
     */
    public function downloadFile(string $token, int $fileId): void
    {
        // Valider le token
        $validationData = $this->tokenService->validateToken($token);

        if (!$validationData) {
            http_response_code(404);
            echo "Fichier non trouvé";
            return;
        }

        // Vérifier que le fichier appartient à la campagne
        $file = $this->db->fetch(
            "SELECT * FROM files
             WHERE id = :id
             AND campaign_id = :campaign_id",
            [
                'id' => $fileId,
                'campaign_id' => $validationData['campaign_id']
            ]
        );

        if (!$file) {
            http_response_code(404);
            echo "Fichier non trouvé";
            return;
        }

        // Servir le fichier
        $filePath = __DIR__ . '/../../' . $file['chemin'];

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Fichier physique non trouvé";
            return;
        }

        // Headers pour le téléchargement
        header('Content-Type: ' . $file['type_mime']);
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: inline; filename="' . $file['nom_original'] . '"');
        header('Cache-Control: public, max-age=3600');

        readfile($filePath);
    }
}
