<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Twig\Environment;

class SupportsController
{
    private $db;
    private $twig;

    public function __construct(Database $database, Environment $twig)
    {
        $this->db = $database;
        $this->twig = $twig;
    }

    public function index(): void
    {
        $supports = $this->db->fetchAll("SELECT * FROM supports ORDER BY ordre_affichage, nom");

        echo $this->twig->render('supports/index.twig', [
            'supports' => $supports
        ]);
    }

    public function create(): void
    {
        echo $this->twig->render('supports/create.twig');
    }

    public function store(): void
    {
        $nom = $_POST['nom'] ?? '';
        $type = $_POST['type'] ?? 'physique';
        $capacite_max = !empty($_POST['capacite_max']) ? (int)$_POST['capacite_max'] : null;
        $actif = isset($_POST['actif']) ? 1 : 0;
        $ordre_affichage = !empty($_POST['ordre_affichage']) ? (int)$_POST['ordre_affichage'] : 0;

        $this->db->insert('supports', [
            'nom' => $nom,
            'type' => $type,
            'capacite_max' => $capacite_max,
            'actif' => $actif,
            'ordre_affichage' => $ordre_affichage
        ]);

        header('Location: /agora/public/supports');
        exit;
    }

    public function edit(?string $id): void
    {
        $support = $this->db->fetch("SELECT * FROM supports WHERE id = :id", ['id' => $id]);

        echo $this->twig->render('supports/edit.twig', [
            'support' => $support
        ]);
    }

    public function update(?string $id): void
    {
        $nom = $_POST['nom'] ?? '';
        $type = $_POST['type'] ?? 'physique';
        $capacite_max = !empty($_POST['capacite_max']) ? (int)$_POST['capacite_max'] : null;
        $actif = isset($_POST['actif']) ? 1 : 0;
        $ordre_affichage = !empty($_POST['ordre_affichage']) ? (int)$_POST['ordre_affichage'] : 0;

        $this->db->update('supports', [
            'nom' => $nom,
            'type' => $type,
            'capacite_max' => $capacite_max,
            'actif' => $actif,
            'ordre_affichage' => $ordre_affichage
        ], 'id = :id', ['id' => $id]);

        header('Location: /agora/public/supports');
        exit;
    }

    public function delete(?string $id): void
    {
        $this->db->delete('supports', 'id = :id', ['id' => $id]);
        header('Location: /agora/public/supports');
        exit;
    }
}
