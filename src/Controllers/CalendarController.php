<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Agora\Repositories\CampaignRepository;
use Agora\Middleware\Auth;
use Twig\Environment;

class CalendarController
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

        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('m');

        // Générer le calendrier
        $firstDay = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = date('t', $firstDay);
        $dayOfWeek = date('N', $firstDay); // 1=Lundi, 7=Dimanche

        // Récupérer les campagnes du mois
        $campaigns = $this->db->fetchAll(
            "SELECT * FROM campaigns
             WHERE YEAR(date_event_debut) = :year AND MONTH(date_event_debut) = :month
             ORDER BY date_event_debut",
            ['year' => $year, 'month' => $month]
        );

        // Organiser par jour
        $campaignsByDay = [];
        foreach ($campaigns as $campaign) {
            $day = date('j', strtotime($campaign['date_event_debut']));
            if (!isset($campaignsByDay[$day])) {
                $campaignsByDay[$day] = [];
            }

            $colorClass = 'bg-blue-100 text-blue-800';
            if ($campaign['statut'] == 'en_validation') $colorClass = 'bg-orange-100 text-orange-800';
            if ($campaign['statut'] == 'validee') $colorClass = 'bg-green-100 text-green-800';

            $campaign['color_class'] = $colorClass;
            $campaignsByDay[$day][] = $campaign;
        }

        // Générer les semaines
        $weeks = [];
        $currentWeek = [];

        // Jours vides avant le 1er
        for ($i = 1; $i < $dayOfWeek; $i++) {
            $currentWeek[] = ['day' => '', 'is_current_month' => false, 'campaigns' => []];
        }

        // Jours du mois
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentWeek[] = [
                'day' => $day,
                'is_current_month' => true,
                'is_today' => ($day == date('j') && $month == date('m') && $year == date('Y')),
                'campaigns' => $campaignsByDay[$day] ?? []
            ];

            if (count($currentWeek) == 7) {
                $weeks[] = $currentWeek;
                $currentWeek = [];
            }
        }

        // Compléter la dernière semaine
        while (count($currentWeek) < 7) {
            $currentWeek[] = ['day' => '', 'is_current_month' => false, 'campaigns' => []];
        }
        if (!empty($currentWeek)) {
            $weeks[] = $currentWeek;
        }

        // Récupérer toutes les campagnes avec supports
        $allCampaigns = $this->db->fetchAll("
            SELECT c.*, GROUP_CONCAT(s.nom) as supports
            FROM campaigns c
            LEFT JOIN campaign_supports cs ON c.id = cs.campaign_id
            LEFT JOIN supports s ON cs.support_id = s.id
            GROUP BY c.id
            ORDER BY c.date_event_debut
        ");

        // Récupérer tous les supports
        $supports = $this->db->fetchAll("SELECT DISTINCT s.nom FROM supports s WHERE s.actif = 1 ORDER BY s.ordre_affichage, s.nom");

        // Ajouter couleurs et formatter supports
        foreach ($allCampaigns as &$c) {
            if ($c['statut'] == 'en_validation') $c['color'] = '#f97316';
            elseif ($c['statut'] == 'validee') $c['color'] = '#22c55e';
            else $c['color'] = '#3b82f6';

            $c['supports_array'] = $c['supports'] ? explode(',', $c['supports']) : [];
        }

        echo $this->twig->render('admin/calendar.twig', [
            'all_campaigns' => $allCampaigns,
            'all_supports' => $supports,
        ]);
    }
}
