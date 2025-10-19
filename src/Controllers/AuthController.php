<?php

namespace Agora\Controllers;

use Agora\Services\Database;
use Agora\Repositories\UserRepository;
use Twig\Environment;

class AuthController
{
    private $db;
    private $twig;
    private $userRepository;

    public function __construct(Database $db, Environment $twig)
    {
        $this->db = $db;
        $this->twig = $twig;
        $this->userRepository = new UserRepository($db);
    }

    public function login(): void
    {
        // Si déjà connecté, rediriger vers dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: /agora/public/dashboard');
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'Veuillez remplir tous les champs.';
            } else {
                $user = $this->userRepository->findByEmail($email);

                if ($user && $user->getActif() && $user->verifyPassword($password)) {
                    // Connexion réussie
                    $_SESSION['user_id'] = $user->getId();
                    $_SESSION['user_email'] = $user->getEmail();
                    $_SESSION['user_nom'] = $user->getNom();
                    $_SESSION['user_prenom'] = $user->getPrenom();
                    $_SESSION['user_roles'] = $user->getRoles();
                    $_SESSION['is_admin'] = $user->isAdmin();

                    // Redirection vers dashboard
                    header('Location: /agora/public/dashboard');
                    exit;
                } else {
                    $error = 'Email ou mot de passe incorrect.';
                }
            }
        }

        echo $this->twig->render('auth/login.twig', [
            'error' => $error
        ]);
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: /agora/public/auth/login');
        exit;
    }

    public static function checkAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /agora/public/auth/login');
            exit;
        }
    }

    public static function checkAdmin(): void
    {
        self::checkAuth();
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            http_response_code(403);
            die('Accès refusé. Vous devez être administrateur.');
        }
    }
}
