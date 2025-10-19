<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Agora\Repositories\CampaignRepository;
use Agora\Middleware\Auth;
use Twig\Environment;

class DashboardController
{
    private $db;
    private $twig;
    private $campaignRepository;

    public function __construct(Database $db, Environment $twig)
    {
        $this->db = $db;
        $this->twig = $twig;
        $this->campaignRepository = new CampaignRepository($db);
    }

    public function index(): void
    {
        Auth::requireAuth();

        // Récupérer le filtre
        $filter = $_GET['filter'] ?? 'all';

        // Récupérer les statistiques
        $stats = $this->campaignRepository->getStats();

        // Récupérer les campagnes selon le filtre
        $recentCampaigns = $this->campaignRepository->getRecentCampaigns(10, $filter);

        // Récupérer les campagnes pour la timeline des 30 prochains jours
        $timelineCampaigns = $this->campaignRepository->getTimelineCampaigns(30);

        echo $this->twig->render('admin/dashboard.twig', [
            'stats' => $stats,
            'campaigns' => $recentCampaigns,
            'timeline' => $timelineCampaigns,
            'filter' => $filter,
        ]);
    }
}
