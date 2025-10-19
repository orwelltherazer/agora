<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Agora\Services\MailService;
use Agora\Services\CampaignLogService;
use Agora\Repositories\CampaignRepository;
use Agora\Middleware\Auth;
use Twig\Environment;

class CampaignController
{
    private $db;
    private $twig;
    private $campaignRepository;
    private $mailService;
    private $logService;

    public function __construct(Database $db, Environment $twig, MailService $mailService = null)
    {
        $this->db = $db;
        $this->twig = $twig;
        $this->campaignRepository = new CampaignRepository($db);
        $this->mailService = $mailService;
        $this->logService = new CampaignLogService($db);
    }

    public function index(): void
    {
        Auth::requireAuth();

        $filters = [];

        if (!empty($_GET['statut'])) {
            $filters['statut'] = $_GET['statut'];
        }

        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        $campaigns = $this->campaignRepository->findAllWithDetails($filters);

        echo $this->twig->render('campaigns/index.twig', [
            'campaigns' => $campaigns,
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();

        $supports = $this->db->fetchAll("SELECT * FROM supports WHERE actif = 1 ORDER BY ordre_affichage, nom");
        $users = $this->db->fetchAll("SELECT * FROM users WHERE actif = 1 ORDER BY nom, prenom");

        echo $this->twig->render('campaigns/create.twig', [
            'supports' => $supports,
            'users' => $users,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $statut = $_POST['action'] == 'validation' ? 'en_validation' : 'brouillon';

        $campaignId = $this->db->insert('campaigns', [
            'titre' => $_POST['titre'],
            'description' => $_POST['description'] ?? null,
            'demandeur' => $_POST['demandeur'],
            'demandeur_email' => $_POST['demandeur_email'] ?? null,
            'date_event_debut' => $_POST['date_event_debut'],
            'date_event_fin' => $_POST['date_event_fin'] ?? null,
            'date_campagne_debut' => $_POST['date_campagne_debut'] ?? null,
            'date_campagne_fin' => $_POST['date_campagne_fin'] ?? null,
            'statut' => $statut,
            'created_by' => $_SESSION['user_id'],
        ]);

        // Supports
        if (!empty($_POST['supports'])) {
            foreach ($_POST['supports'] as $supportId) {
                $this->db->insert('campaign_supports', [
                    'campaign_id' => $campaignId,
                    'support_id' => $supportId,
                ]);
            }
        }

        // Validateurs
        if (!empty($_POST['validateurs'])) {
            $ordre = 1;
            foreach ($_POST['validateurs'] as $userId) {
                $this->db->insert('campaign_validators', [
                    'campaign_id' => $campaignId,
                    'user_id' => $userId,
                    'ordre' => $ordre++,
                ]);
            }
        }

        // Upload de fichiers
        $fileCount = 0;
        if (!empty($_FILES['visuels']['name'][0])) {
            $fileCount = $this->handleFileUpload($campaignId, $_FILES['visuels']);
        }

        // Log de création
        $this->logService->logCreated($campaignId, $_SESSION['user_id'], $_POST['titre']);

        if ($fileCount > 0) {
            $this->logService->logFilesAdded($campaignId, $_SESSION['user_id'], $fileCount);
        }

        // Envoyer les emails si la campagne passe en validation
        if ($statut === 'en_validation') {
            $this->sendValidationEmails($campaignId);
        }

        header('Location: /agora/public/campaigns/show/' . $campaignId);
        exit;
    }

    public function edit(?string $id): void
    {
        Auth::requireAuth();

        $campaign = $this->db->fetch("SELECT * FROM campaigns WHERE id = :id", ['id' => $id]);

        if (!$campaign) {
            http_response_code(404);
            echo "Campagne non trouvée";
            return;
        }

        $supports = $this->db->fetchAll("SELECT * FROM supports WHERE actif = 1 ORDER BY ordre_affichage, nom");
        $users = $this->db->fetchAll("SELECT * FROM users WHERE actif = 1 ORDER BY nom, prenom");

        $campaignSupports = $this->db->fetchAll("SELECT support_id FROM campaign_supports WHERE campaign_id = :id", ['id' => $id]);
        $campaignValidators = $this->db->fetchAll("SELECT user_id FROM campaign_validators WHERE campaign_id = :id", ['id' => $id]);
        $files = $this->db->fetchAll("SELECT * FROM files WHERE campaign_id = :id AND est_version_actuelle = 1 ORDER BY uploaded_at DESC", ['id' => $id]);

        echo $this->twig->render('campaigns/edit.twig', [
            'campaign' => $campaign,
            'supports' => $supports,
            'users' => $users,
            'campaign_support_ids' => array_column($campaignSupports, 'support_id'),
            'campaign_validator_ids' => array_column($campaignValidators, 'user_id'),
            'files' => $files,
        ]);
    }

    public function update(?string $id): void
    {
        Auth::requireAuth();

        // Récupérer l'ancienne campagne complète pour le log
        $oldCampaign = $this->db->fetch("SELECT * FROM campaigns WHERE id = :id", ['id' => $id]);
        $oldStatus = $oldCampaign['statut'] ?? null;
        $newStatus = $_POST['statut'];

        $newValues = [
            'titre' => $_POST['titre'],
            'description' => $_POST['description'] ?? null,
            'demandeur' => $_POST['demandeur'],
            'demandeur_email' => $_POST['demandeur_email'] ?? null,
            'date_event_debut' => $_POST['date_event_debut'],
            'date_event_fin' => $_POST['date_event_fin'] ?? null,
            'date_campagne_debut' => $_POST['date_campagne_debut'] ?? null,
            'date_campagne_fin' => $_POST['date_campagne_fin'] ?? null,
            'statut' => $newStatus,
        ];

        $this->db->update('campaigns', $newValues, 'id = :id', ['id' => $id]);

        // Supprimer anciens supports et validateurs
        $this->db->query("DELETE FROM campaign_supports WHERE campaign_id = :id", ['id' => $id]);
        $this->db->query("DELETE FROM campaign_validators WHERE campaign_id = :id", ['id' => $id]);

        // Réinsérer supports
        if (!empty($_POST['supports'])) {
            foreach ($_POST['supports'] as $supportId) {
                $this->db->insert('campaign_supports', [
                    'campaign_id' => $id,
                    'support_id' => $supportId,
                ]);
            }
        }

        // Réinsérer validateurs
        if (!empty($_POST['validateurs'])) {
            $ordre = 1;
            foreach ($_POST['validateurs'] as $userId) {
                $this->db->insert('campaign_validators', [
                    'campaign_id' => $id,
                    'user_id' => $userId,
                    'ordre' => $ordre++,
                ]);
            }
        }

        // Upload de nouveaux fichiers
        $fileCount = 0;
        if (!empty($_FILES['visuels']['name'][0])) {
            $fileCount = $this->handleFileUpload($id, $_FILES['visuels']);
        }

        // Log de la modification
        $this->logService->logUpdated((int)$id, $_SESSION['user_id'], $oldCampaign, $newValues);

        // Log du changement de statut si applicable
        if ($oldStatus !== $newStatus) {
            $this->logService->logStatusChanged((int)$id, $_SESSION['user_id'], $oldStatus, $newStatus);
        }

        if ($fileCount > 0) {
            $this->logService->logFilesAdded((int)$id, $_SESSION['user_id'], $fileCount);
        }

        // Envoyer les emails si le statut passe à "en_validation"
        if ($oldStatus !== 'en_validation' && $newStatus === 'en_validation') {
            $this->sendValidationEmails((int)$id);
        }

        header('Location: /agora/public/campaigns/show/' . $id);
        exit;
    }

    public function show(?string $id): void
    {
        Auth::requireAuth();

        $campaign = $this->db->fetch("SELECT * FROM campaigns WHERE id = :id", ['id' => $id]);

        if (!$campaign) {
            http_response_code(404);
            echo "Campagne non trouvée";
            return;
        }

        // Récupérer supports
        $supports = $this->db->fetchAll("
            SELECT s.* FROM supports s
            JOIN campaign_supports cs ON s.id = cs.support_id
            WHERE cs.campaign_id = :id
        ", ['id' => $id]);

        // Récupérer validateurs
        $validateurs = $this->db->fetchAll("
            SELECT u.*, v.action as validation_statut
            FROM campaign_validators cv
            JOIN users u ON cv.user_id = u.id
            LEFT JOIN validations v ON v.campaign_id = cv.campaign_id AND v.user_id = cv.user_id
            WHERE cv.campaign_id = :id
            ORDER BY cv.ordre
        ", ['id' => $id]);

        // Récupérer fichiers
        $files = $this->db->fetchAll("SELECT * FROM files WHERE campaign_id = :id AND est_version_actuelle = 1 ORDER BY uploaded_at DESC", ['id' => $id]);

        echo $this->twig->render('campaigns/show.twig', [
            'campaign' => $campaign,
            'supports' => $supports,
            'validateurs' => $validateurs,
            'files' => $files,
        ]);
    }

    private function handleFileUpload(int $campaignId, array $files): int
    {
        $baseDir = __DIR__ . '/../../storage/uploads/';
        $uploadedCount = 0;

        // Créer sous-répertoire YYYY/MM
        $year = date('Y');
        $month = date('m');
        $subDir = $year . '/' . $month . '/';
        $uploadDir = $baseDir . $subDir;

        // Créer les répertoires si nécessaire
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($files['name'] as $key => $filename) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $tmpName = $files['tmp_name'][$key];
                $size = $files['size'][$key];
                $mimeType = $files['type'][$key];

                // Générer un nom unique
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $uniqueName = uniqid() . '_' . time() . '.' . $extension;
                $destination = $uploadDir . $uniqueName;

                if (move_uploaded_file($tmpName, $destination)) {
                    $this->db->insert('files', [
                        'campaign_id' => $campaignId,
                        'nom_original' => $filename,
                        'nom_stockage' => $uniqueName,
                        'chemin' => 'storage/uploads/' . $subDir . $uniqueName,
                        'type_mime' => $mimeType,
                        'taille' => $size,
                        'uploaded_by' => $_SESSION['user_id'],
                    ]);
                    $uploadedCount++;
                }
            }
        }

        return $uploadedCount;
    }

    public function deleteFile(?string $id): void
    {
        Auth::requireAuth();

        $file = $this->db->fetch("SELECT * FROM files WHERE id = :id", ['id' => $id]);

        if ($file) {
            $campaignId = $file['campaign_id'];
            $filename = $file['nom_original'];

            // Supprimer le fichier physique - utiliser le chemin complet
            $filePath = __DIR__ . '/../../' . $file['chemin'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Supprimer l'entrée en base
            $this->db->delete('files', 'id = :id', ['id' => $id]);

            // Log de la suppression
            $this->logService->logFileDeleted((int)$campaignId, $_SESSION['user_id'], $filename);

            header('Location: /agora/public/campaigns/edit/' . $campaignId);
            exit;
        }
    }

    public function archive(?string $id): void
    {
        Auth::requireAuth();

        $this->db->update('campaigns', [
            'statut' => 'archivee',
            'archived_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $id]);

        // Log de l'archivage
        $this->logService->logArchived((int)$id, $_SESSION['user_id']);

        header('Location: /agora/public/campaigns/show/' . $id);
        exit;
    }

    public function unarchive(?string $id): void
    {
        Auth::requireAuth();

        // Récupérer l'ancienne campagne pour déterminer le statut de retour
        $campaign = $this->db->fetch("SELECT * FROM campaigns WHERE id = :id", ['id' => $id]);

        if (!$campaign) {
            http_response_code(404);
            return;
        }

        // Déterminer le statut de retour (publiee ou validee par défaut)
        $newStatus = 'publiee';

        $this->db->update('campaigns', [
            'statut' => $newStatus,
            'archived_at' => null,
        ], 'id = :id', ['id' => $id]);

        // Log du désarchivage
        $this->logService->logUnarchived((int)$id, $_SESSION['user_id']);

        header('Location: /agora/public/campaigns/show/' . $id);
        exit;
    }

    /**
     * Envoie les emails de demande de validation aux validateurs
     */
    private function sendValidationEmails(int $campaignId): void
    {
        if (!$this->mailService) {
            return; // Service email non configuré
        }

        // Récupérer les informations de la campagne
        $campaign = $this->db->fetch("SELECT * FROM campaigns WHERE id = :id", ['id' => $campaignId]);

        if (!$campaign) {
            return;
        }

        // Récupérer les fichiers de la campagne
        $files = $this->db->fetchAll(
            "SELECT * FROM files
             WHERE campaign_id = :campaign_id
             AND est_version_actuelle = 1
             ORDER BY uploaded_at DESC",
            ['campaign_id' => $campaignId]
        );

        // Récupérer les validateurs
        $validators = $this->db->fetchAll("
            SELECT u.id, u.nom, u.prenom, u.email
            FROM campaign_validators cv
            JOIN users u ON cv.user_id = u.id
            WHERE cv.campaign_id = :campaign_id
            ORDER BY cv.ordre
        ", ['campaign_id' => $campaignId]);

        // Initialiser le service de tokens
        $tokenService = new \Agora\Services\ValidationTokenService($this->db);

        // Déterminer l'URL de base selon le mode (direct ou passerelle)
        // Utilise config() qui charge depuis la base de données
        $validationMode = config('validation.mode', 'direct');
        $baseUrl = ($validationMode === 'passerelle')
            ? config('validation.url')
            : config('app.url');

        // Envoyer un email à chaque validateur avec un token unique
        foreach ($validators as $validator) {
            // Générer un token unique pour ce validateur
            $tokenExpiry = config('validation.tokendays', 30);
            $token = $tokenService->generateToken($campaignId, $validator['id'], $tokenExpiry);

            // Générer l'URL publique de validation (adapté au mode)
            $validationUrl = $baseUrl . '/validate/' . $token;

            // Envoyer l'email avec les fichiers en pièces jointes
            $this->mailService->sendValidationRequest($campaign, $validator, $validationUrl, $files);
        }
    }
}
