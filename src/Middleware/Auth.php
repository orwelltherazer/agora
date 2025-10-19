<?php

namespace Agora\Middleware;

class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'nom' => $_SESSION['user_nom'] ?? null,
            'prenom' => $_SESSION['user_prenom'] ?? null,
            'roles' => $_SESSION['user_roles'] ?? [],
            'is_admin' => $_SESSION['is_admin'] ?? false,
        ];
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return $_SESSION['is_admin'] ?? false;
    }

    public static function hasRole(string $role): bool
    {
        $roles = $_SESSION['user_roles'] ?? [];
        return in_array($role, $roles);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: /agora/public/auth/login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            die('Accès refusé. Vous devez être administrateur.');
        }
    }
}
