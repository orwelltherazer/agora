<?php

/**
 * Script de test d'email accessible via navigateur
 * URL: http://localhost/agora/tests/test_email_web.php
 */

session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers/functions.php';

$appConfig = require __DIR__ . '/../config/app.php';

$message = '';
$messageType = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $email = $_POST['email'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse email invalide';
        $messageType = 'error';
    } else {
        // Vérification de la configuration via la base de données
        $smtpUsername = config('email.smtp_username', '');
        $smtpPassword = config('email.smtp_password', '');

        if (empty($smtpUsername) || empty($smtpPassword)) {
            $message = 'Les identifiants SMTP ne sont pas configurés. Accédez à /settings > Email (SMTP) pour configurer les paramètres email.';
            $messageType = 'error';
        } else {
            try {
                // Initialisation de Twig
                $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
                $twig = new \Twig\Environment($loader, ['cache' => false]);
                $twig->addGlobal('app', $appConfig);

                // Initialisation du service mail (utilise config() en interne)
                $mailService = new Agora\Services\MailService([], $twig);

                // Données de test
                $testCampaign = [
                    'id' => 999,
                    'titre' => 'Campagne de test - Envoi Email',
                    'description' => 'Test du système d\'envoi d\'emails pour AGORA.',
                    'demandeur' => 'Administrateur Test',
                    'demandeur_email' => 'admin@test.com',
                    'date_event_debut' => date('Y-m-d', strtotime('+7 days')),
                    'date_event_fin' => date('Y-m-d', strtotime('+8 days')),
                    'date_campagne_debut' => date('Y-m-d', strtotime('+5 days')),
                    'statut' => 'en_validation',
                    'priorite' => 'haute',
                ];

                $testValidator = [
                    'id' => 1,
                    'nom' => 'Test',
                    'prenom' => 'Utilisateur',
                    'email' => $email,
                ];

                $validationUrl = $appConfig['url'] . '/campaigns/show/999';

                // Envoi de l'email
                $result = $mailService->sendValidationRequest($testCampaign, $testValidator, $validationUrl);

                if ($result) {
                    $message = "✅ Email envoyé avec succès à <strong>$email</strong>!<br>Vérifiez votre boîte mail (et le dossier spam).";
                    $messageType = 'success';
                } else {
                    $message = '❌ Erreur lors de l\'envoi de l\'email. Vérifiez les logs PHP.';
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = '❌ Exception: ' . htmlspecialchars($e->getMessage());
                $messageType = 'error';
            }
        }
    }
}

// Récupération de la config pour l'affichage
$smtpUsername = config('email.smtp_username', '');
$smtpPassword = config('email.smtp_password', '');
$smtpConfigured = !empty($smtpUsername) && !empty($smtpPassword);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email - AGORA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="mb-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
                    <i class="fas fa-envelope text-2xl text-indigo-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Test d'envoi d'email</h1>
                <p class="text-gray-600 mt-2">Vérification du système d'emails AGORA</p>
            </div>

            <!-- Configuration Status -->
            <div class="mb-6 p-4 rounded-lg <?php echo $smtpConfigured ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $smtpConfigured ? 'fa-check-circle text-green-600' : 'fa-exclamation-triangle text-red-600'; ?> mr-3"></i>
                    <div>
                        <p class="font-semibold <?php echo $smtpConfigured ? 'text-green-800' : 'text-red-800'; ?>">
                            <?php if ($smtpConfigured): ?>
                                Configuration SMTP OK
                            <?php else: ?>
                                Configuration SMTP manquante
                            <?php endif; ?>
                        </p>
                        <?php if (!$smtpConfigured): ?>
                        <p class="text-sm text-red-700 mt-1">
                            Veuillez configurer les identifiants SMTP dans <a href="<?php echo $appConfig['url']; ?>/settings" class="underline">/settings > Email (SMTP)</a>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SMTP Info -->
            <?php if ($smtpConfigured): ?>
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h3 class="font-semibold text-gray-900 mb-3">📋 Configuration SMTP</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex">
                        <span class="font-medium text-gray-600 w-32">Serveur:</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars(config('email.smtp_host', 'N/A')); ?></span>
                    </div>
                    <div class="flex">
                        <span class="font-medium text-gray-600 w-32">Port:</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars(config('email.smtp_port', 'N/A')); ?></span>
                    </div>
                    <div class="flex">
                        <span class="font-medium text-gray-600 w-32">Encryption:</span>
                        <span class="text-gray-900"><?php echo strtoupper(htmlspecialchars(config('email.smtp_encryption', 'N/A'))); ?></span>
                    </div>
                    <div class="flex">
                        <span class="font-medium text-gray-600 w-32">Utilisateur:</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars(config('email.smtp_username', 'N/A')); ?></span>
                    </div>
                    <div class="flex">
                        <span class="font-medium text-gray-600 w-32">Expéditeur:</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars(config('email.from_address', 'N/A')); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Message -->
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                <p class="<?php echo $messageType === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                    <?php echo $message; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        📧 Email destinataire
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        placeholder="votre-email@example.com"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                    <p class="text-xs text-gray-500 mt-1">
                        L'email de test sera envoyé à cette adresse
                    </p>
                </div>

                <button
                    type="submit"
                    name="send_test"
                    class="w-full px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center"
                    <?php echo !$smtpConfigured ? 'disabled' : ''; ?>
                >
                    <i class="fas fa-paper-plane mr-2"></i>
                    Envoyer un email de test
                </button>
            </form>

            <!-- Info -->
            <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Informations</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• L'email de test simule une demande de validation de campagne</li>
                    <li>• Vérifiez votre dossier spam si vous ne recevez rien</li>
                    <li>• Pour Gmail, utilisez un mot de passe d'application</li>
                    <li>• Les logs d'erreur sont dans le fichier error_log de PHP</li>
                </ul>
            </div>

            <!-- Back link -->
            <div class="mt-6 text-center">
                <a href="<?php echo $appConfig['url']; ?>/dashboard" class="text-indigo-600 hover:text-indigo-800 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Retour au dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
