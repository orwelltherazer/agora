<?php

/**
 * Script de test pour l'envoi d'emails
 *
 * Usage: php tests/test_email.php votre-email@example.com
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers/functions.php';

// Vérifier qu'un email destinataire est fourni
if ($argc < 2) {
    echo "❌ Usage: php tests/test_email.php votre-email@example.com\n";
    exit(1);
}

$emailDestinataire = $argv[1];

// Valider le format de l'email
if (!filter_var($emailDestinataire, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Erreur: '$emailDestinataire' n'est pas une adresse email valide\n";
    exit(1);
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "   TEST D'ENVOI D'EMAIL - AGORA\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Chargement de la configuration
echo "📋 Chargement de la configuration...\n";
$appConfig = require __DIR__ . '/../config/app.php';

// Vérification de la configuration via la base de données
echo "🔍 Vérification de la configuration SMTP...\n";
$smtpUsername = config('email.smtp_username', '');
$smtpPassword = config('email.smtp_password', '');

if (empty($smtpUsername) || empty($smtpPassword)) {
    echo "❌ ERREUR: Les identifiants SMTP ne sont pas configurés!\n";
    echo "   Veuillez accéder à /settings > Email (SMTP) pour configurer les paramètres email.\n\n";
    exit(1);
}

echo "   ✓ Host: " . config('email.smtp_host', 'N/A') . "\n";
echo "   ✓ Port: " . config('email.smtp_port', 'N/A') . "\n";
echo "   ✓ Username: " . config('email.smtp_username', 'N/A') . "\n";
echo "   ✓ Encryption: " . config('email.smtp_encryption', 'N/A') . "\n";
echo "   ✓ From: " . config('email.from_address', 'N/A') . "\n\n";

// Initialisation de Twig
echo "🎨 Initialisation de Twig...\n";
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,
]);
$twig->addGlobal('app', $appConfig);
echo "   ✓ Twig initialisé\n\n";

// Initialisation du service mail
echo "📧 Initialisation du service mail...\n";
try {
    $mailService = new Agora\Services\MailService([], $twig); // Utilise config() en interne
    echo "   ✓ Service mail initialisé\n\n";
} catch (Exception $e) {
    echo "❌ ERREUR: Impossible d'initialiser le service mail\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}

// Préparation des données de test
echo "📝 Préparation des données de test...\n";
$testCampaign = [
    'id' => 999,
    'titre' => 'Campagne de test - Envoi Email',
    'description' => 'Ceci est une campagne de test pour vérifier que le système d\'envoi d\'emails fonctionne correctement.',
    'demandeur' => 'Test Utilisateur',
    'demandeur_email' => 'test@example.com',
    'date_event_debut' => date('Y-m-d', strtotime('+7 days')),
    'date_event_fin' => date('Y-m-d', strtotime('+8 days')),
    'date_campagne_debut' => date('Y-m-d', strtotime('+5 days')),
    'date_campagne_fin' => date('Y-m-d', strtotime('+10 days')),
    'statut' => 'en_validation',
    'priorite' => 'haute',
];

$testValidator = [
    'id' => 1,
    'nom' => 'Testeur',
    'prenom' => 'Jean',
    'email' => $emailDestinataire,
];

$validationUrl = $appConfig['url'] . '/campaigns/show/999';

echo "   ✓ Campagne: " . $testCampaign['titre'] . "\n";
echo "   ✓ Destinataire: " . $testValidator['prenom'] . " " . $testValidator['nom'] . " <" . $testValidator['email'] . ">\n";
echo "   ✓ URL: " . $validationUrl . "\n\n";

// Envoi de l'email de test
echo "🚀 Envoi de l'email de test...\n";
echo "   Destinataire: " . $emailDestinataire . "\n";
echo "   Envoi en cours...\n";

try {
    $result = $mailService->sendValidationRequest($testCampaign, $testValidator, $validationUrl);

    if ($result) {
        echo "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ SUCCESS! L'email a été envoyé avec succès!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        echo "📬 Vérifiez votre boîte mail: " . $emailDestinataire . "\n";
        echo "   N'oubliez pas de vérifier le dossier spam/courrier indésirable\n\n";
        echo "📋 Détails de l'email envoyé:\n";
        echo "   • Sujet: Demande de validation - " . $testCampaign['titre'] . "\n";
        echo "   • Template: emails/validation.twig\n";
        echo "   • Priorité: " . strtoupper($testCampaign['priorite']) . "\n\n";
        exit(0);
    } else {
        echo "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "❌ ERREUR: L'envoi de l'email a échoué\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        echo "💡 Suggestions de dépannage:\n";
        echo "   1. Vérifiez les logs PHP pour plus de détails\n";
        echo "   2. Vérifiez que les identifiants SMTP sont corrects\n";
        echo "   3. Vérifiez votre connexion internet\n";
        echo "   4. Vérifiez que le port " . $mailConfig['smtp']['port'] . " n'est pas bloqué par le pare-feu\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "❌ EXCEPTION: Une erreur s'est produite\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "💡 Suggestions:\n";
    echo "   • Pour Gmail: Utilisez un mot de passe d'application\n";
    echo "   • Vérifiez que la validation en 2 étapes est activée\n";
    echo "   • Configurez les paramètres SMTP via /settings > Email (SMTP)\n";
    echo "   • Testez avec telnet: telnet " . config('email.smtp_host', '') . " " . config('email.smtp_port', '') . "\n\n";
    exit(1);
}
