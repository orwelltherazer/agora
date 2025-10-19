<?php

namespace Agora\Repositories;

use Agora\Services\Database;
use Agora\Models\User;

class UserRepository
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        $sql = "SELECT u.*, GROUP_CONCAT(r.nom) as roles
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                GROUP BY u.id
                ORDER BY u.nom, u.prenom";

        $data = $this->db->fetchAll($sql);

        return array_map(function($row) {
            $row['roles'] = $row['roles'] ? explode(',', $row['roles']) : [];
            return new User($row);
        }, $data);
    }

    public function findById(int $id): ?User
    {
        $sql = "SELECT u.*, GROUP_CONCAT(r.nom) as roles
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.id = :id
                GROUP BY u.id";

        $data = $this->db->fetch($sql, ['id' => $id]);

        if (!$data) {
            return null;
        }

        $data['roles'] = $data['roles'] ? explode(',', $data['roles']) : [];
        return new User($data);
    }

    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT u.*, GROUP_CONCAT(r.nom) as roles
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.email = :email
                GROUP BY u.id";

        $data = $this->db->fetch($sql, ['email' => $email]);

        if (!$data) {
            return null;
        }

        $data['roles'] = $data['roles'] ? explode(',', $data['roles']) : [];
        return new User($data);
    }

    public function create(User $user): int
    {
        $data = [
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'actif' => $user->getActif() ? 1 : 0,
        ];

        return $this->db->insert('users', $data);
    }

    public function update(User $user): bool
    {
        $data = [
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'actif' => $user->getActif() ? 1 : 0,
        ];

        // Ne mettre à jour le mot de passe que s'il est défini
        if ($user->getPassword()) {
            $data['password'] = $user->getPassword();
        }

        $affected = $this->db->update('users', $data, 'id = :id', ['id' => $user->getId()]);
        return $affected > 0;
    }

    public function delete(int $id): bool
    {
        $affected = $this->db->delete('users', 'id = :id', ['id' => $id]);
        return $affected > 0;
    }

    public function assignRole(int $userId, int $roleId): void
    {
        $this->db->insert('user_roles', [
            'user_id' => $userId,
            'role_id' => $roleId
        ]);
    }

    public function removeRole(int $userId, int $roleId): void
    {
        $this->db->delete('user_roles', 'user_id = :user_id AND role_id = :role_id', [
            'user_id' => $userId,
            'role_id' => $roleId
        ]);
    }

    public function syncRoles(int $userId, array $roleIds): void
    {
        // Supprimer tous les rôles actuels
        $this->db->delete('user_roles', 'user_id = :user_id', ['user_id' => $userId]);

        // Ajouter les nouveaux rôles
        foreach ($roleIds as $roleId) {
            $this->assignRole($userId, $roleId);
        }
    }

    public function getAllRoles(): array
    {
        return $this->db->fetchAll("SELECT * FROM roles ORDER BY nom");
    }

    public function findValidateurs(): array
    {
        $sql = "SELECT DISTINCT u.*, GROUP_CONCAT(r.nom) as roles
                FROM users u
                INNER JOIN user_roles ur ON u.id = ur.user_id
                INNER JOIN roles r ON ur.role_id = r.id
                WHERE r.nom = 'validateur' AND u.actif = 1
                GROUP BY u.id
                ORDER BY u.nom, u.prenom";

        $data = $this->db->fetchAll($sql);

        return array_map(function($row) {
            $row['roles'] = $row['roles'] ? explode(',', $row['roles']) : [];
            return new User($row);
        }, $data);
    }
}
