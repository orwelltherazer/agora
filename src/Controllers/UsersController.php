<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Twig\Environment;

class UsersController
{
    private $db;
    private $twig;

    public function __construct($database, $twig)
    {
        $this->db = $database;
        $this->twig = $twig;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $actif = $_GET['actif'] ?? '';

        $sql = "SELECT u.*, GROUP_CONCAT(r.nom) as roles
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if ($actif !== '') {
            $sql .= " AND u.actif = :actif";
            $params['actif'] = $actif;
        }

        $sql .= " GROUP BY u.id ORDER BY u.nom, u.prenom";

        $users = $this->db->fetchAll($sql, $params);

        // Filtrer par rôle si nécessaire
        if ($role) {
            $users = array_filter($users, function($user) use ($role) {
                return $user['roles'] && strpos($user['roles'], $role) !== false;
            });
        }

        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY nom");

        echo $this->twig->render('users/index.twig', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'actif' => $actif
            ]
        ]);
    }

    public function create(): void
    {
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY nom");

        echo $this->twig->render('users/create.twig', [
            'roles' => $roles
        ]);
    }

    public function store(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $telephone = $_POST['telephone'] ?? '';
        $actif = isset($_POST['actif']) ? 1 : 0;
        $roleIds = $_POST['roles'] ?? [];

        // Hash du password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $userId = $this->db->insert('users', [
            'email' => $email,
            'password' => $hashedPassword,
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'actif' => $actif
        ]);

        // Associer les rôles
        foreach ($roleIds as $roleId) {
            $this->db->insert('user_roles', [
                'user_id' => $userId,
                'role_id' => $roleId
            ]);
        }

        header('Location: /agora/public/users');
        exit;
    }

    public function edit(?string $id): void
    {
        $user = $this->db->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
        $roles = $this->db->fetchAll("SELECT * FROM roles ORDER BY nom");
        $userRoles = $this->db->fetchAll("SELECT role_id FROM user_roles WHERE user_id = :id", ['id' => $id]);
        $userRoleIds = array_column($userRoles, 'role_id');

        echo $this->twig->render('users/edit.twig', [
            'user' => $user,
            'roles' => $roles,
            'user_role_ids' => $userRoleIds
        ]);
    }

    public function update(?string $id): void
    {
        $email = $_POST['email'] ?? '';
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $telephone = $_POST['telephone'] ?? '';
        $actif = isset($_POST['actif']) ? 1 : 0;
        $roleIds = $_POST['roles'] ?? [];

        $data = [
            'email' => $email,
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'actif' => $actif
        ];

        // Si un nouveau mot de passe est fourni
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $this->db->update('users', $data, 'id = :id', ['id' => $id]);

        // Mettre à jour les rôles
        $this->db->query("DELETE FROM user_roles WHERE user_id = :id", ['id' => $id]);
        foreach ($roleIds as $roleId) {
            $this->db->insert('user_roles', [
                'user_id' => $id,
                'role_id' => $roleId
            ]);
        }

        header('Location: /agora/public/users');
        exit;
    }

    public function delete(?string $id): void
    {
        $this->db->delete('users', 'id = :id', ['id' => $id]);
        header('Location: /agora/public/users');
        exit;
    }
}
