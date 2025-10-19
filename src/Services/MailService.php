<?php

namespace Agora\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Twig\Environment;

class MailService
{
    private $mailer;
    private $twig;
    private $config;

    public function __construct(array $config, Environment $twig)
    {
        $this->config = $config;
        $this->twig = $twig;
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }

    private function configureSMTP(): void
    {
        // Charger les helpers si besoin
        if (!function_exists('config')) {
            require_once __DIR__ . '/../Helpers/functions.php';
        }

        $this->mailer->isSMTP();

        // Charger depuis la base de données via config(), avec fallback sur $this->config
        $this->mailer->Host = config('email.host', $this->config['smtp']['host'] ?? 'localhost');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = config('email.username', $this->config['smtp']['username'] ?? '');
        $this->mailer->Password = config('email.password', $this->config['smtp']['password'] ?? '');
        $this->mailer->SMTPSecure = config('email.encryption', $this->config['smtp']['encryption'] ?? 'tls');
        $this->mailer->Port = (int)config('email.port', $this->config['smtp']['port'] ?? 587);
        $this->mailer->CharSet = 'UTF-8';

        // Configuration de l'expéditeur
        $fromEmail = config('email.fromaddress', $this->config['smtp']['from']['email'] ?? 'noreply@example.com');
        $fromName = config('email.fromname', $this->config['smtp']['from']['name'] ?? 'Agora');

        $this->mailer->setFrom($fromEmail, $fromName);
    }

    /**
     * Envoie un email de demande de validation à un validateur
     */
    public function sendValidationRequest(array $campaign, array $validator, string $validationUrl, array $files = []): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($validator['email'], $validator['prenom'] . ' ' . $validator['nom']);

            $this->mailer->Subject = 'Demande de validation - ' . $campaign['titre'];

            // Joindre les fichiers images (max 5 Mo par fichier)
            $baseDir = __DIR__ . '/../../';
            $attachmentCount = 0;
            foreach ($files as $file) {
                // Limiter aux images et PDFs, max 5 fichiers
                if ($attachmentCount >= 5) break;

                if (strpos($file['type_mime'], 'image/') === 0 || $file['type_mime'] === 'application/pdf') {
                    $filePath = $baseDir . $file['chemin'];
                    if (file_exists($filePath) && filesize($filePath) < 5242880) { // 5 Mo max
                        $this->mailer->addAttachment($filePath, $file['nom_original']);
                        $attachmentCount++;
                    }
                }
            }

            // Rendu du template email
            $htmlBody = $this->twig->render('emails/validation.twig', [
                'campaign' => $campaign,
                'validator' => $validator,
                'validation_url' => $validationUrl,
                'has_attachments' => $attachmentCount > 0,
                'attachment_count' => $attachmentCount,
            ]);

            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email : " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Envoie un email de relance à un validateur
     */
    public function sendReminder(array $campaign, array $validator, string $validationUrl): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($validator['email'], $validator['prenom'] . ' ' . $validator['nom']);

            $this->mailer->Subject = 'Relance : Validation en attente - ' . $campaign['titre'];

            $htmlBody = $this->twig->render('emails/relance.twig', [
                'campaign' => $campaign,
                'validator' => $validator,
                'validation_url' => $validationUrl,
            ]);

            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email : " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Envoie une notification de changement de statut
     */
    public function sendStatusChange(array $campaign, array $recipient, string $oldStatus, string $newStatus): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($recipient['email'], $recipient['prenom'] . ' ' . $recipient['nom']);

            $this->mailer->Subject = 'Changement de statut - ' . $campaign['titre'];

            $htmlBody = $this->twig->render('emails/changement_statut.twig', [
                'campaign' => $campaign,
                'recipient' => $recipient,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email : " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Envoie une alerte pour une deadline proche
     */
    public function sendDeadlineAlert(array $campaign, array $recipient, int $daysLeft): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($recipient['email'], $recipient['prenom'] . ' ' . $recipient['nom']);

            $this->mailer->Subject = 'Alerte deadline - ' . $campaign['titre'];

            $htmlBody = $this->twig->render('emails/deadline.twig', [
                'campaign' => $campaign,
                'recipient' => $recipient,
                'days_left' => $daysLeft,
            ]);

            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email : " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Envoie un email de test
     */
    public function sendTestEmail(string $email, string $name): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($email, $name);

            $this->mailer->Subject = 'Test d\'envoi d\'email - Agora';

            $htmlBody = '<html><body>';
            $htmlBody .= '<h1>Email de test</h1>';
            $htmlBody .= '<p>Bonjour ' . htmlspecialchars($name) . ',</p>';
            $htmlBody .= '<p>Ceci est un email de test envoyé depuis l\'application Agora.</p>';
            $htmlBody .= '<p>Si vous recevez cet email, la configuration SMTP fonctionne correctement.</p>';
            $htmlBody .= '<p>Date et heure : ' . date('d/m/Y H:i:s') . '</p>';
            $htmlBody .= '</body></html>';

            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = 'Email de test - Si vous recevez cet email, la configuration SMTP fonctionne correctement.';

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email de test : " . $this->mailer->ErrorInfo);
            throw $e;
        }
    }
}
