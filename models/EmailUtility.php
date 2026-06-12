<?php
/**
 * Email Utility Model
 * Handles email sending using PHPMailer
 */

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../config/env_loader.php';

class EmailUtility {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $smtpEncryption;
    private $fromEmail;
    private $fromName;
    private $appUrl;

    public function __construct(array $config = []) {
        $this->smtpHost = $config['host'] ?? getenv('SMTP_HOST') ?? 'smtp.gmail.com';
        $this->smtpPort = (int)($config['port'] ?? getenv('SMTP_PORT') ?? 587);
        $this->smtpUsername = $config['username'] ?? getenv('SMTP_USERNAME') ?? 'your-email@gmail.com';
        $this->smtpPassword = $config['password'] ?? getenv('SMTP_PASSWORD') ?? 'your-app-password';
        $this->smtpEncryption = $config['encryption'] ?? getenv('SMTP_ENCRYPTION') ?? PHPMailer::ENCRYPTION_STARTTLS;
        $this->fromEmail = $config['from_email'] ?? getenv('MAIL_FROM_ADDRESS') ?? 'noreply@wafra.com';
        $this->fromName = $config['from_name'] ?? getenv('MAIL_FROM_NAME') ?? 'Wafra';
        
        // Ensure BASE_URL is defined before using it
        if (!defined('BASE_URL')) {
            require_once __DIR__ . '/../config/config.php';
        }
        
        // Get app URL - prioritize config, then env, then BASE_URL, then default
        $appUrl = $config['app_url'] ?? getenv('APP_URL');
        if (empty($appUrl) && defined('BASE_URL')) {
            $appUrl = BASE_URL;
        }
        if (empty($appUrl)) {
            // Fallback: construct from server variables
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $appUrl = $protocol . '://' . $host . '/wafra/wafra-integration';
        }
        $this->appUrl = rtrim($appUrl, '/');
    }

    private function bootstrapMailer(): PHPMailer {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $this->smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtpUsername;
        $mail->Password = $this->smtpPassword;
        $mail->SMTPSecure = $this->smtpEncryption;
        $mail->Port = $this->smtpPort;
        $mail->isHTML(true);
        $mail->setFrom($this->fromEmail, $this->fromName);
        $mail->addReplyTo($this->fromEmail, $this->fromName);
        return $mail;
    }

    public function sendVerificationEmail($toEmail, $verificationToken, $userName) {
        try {
            $mail = $this->bootstrapMailer();
            $mail->addAddress($toEmail, $userName);
            $mail->Subject = 'Verify Your Email Address - Wafra';

            // Ensure appUrl includes the full path to wafra-integration
            $appUrl = $this->appUrl;
            if (strpos($appUrl, 'wafra-integration') === false) {
                $appUrl = rtrim($appUrl, '/') . '/wafra-integration';
            }
            $verificationLink = "{$appUrl}/index.php?action=verify_email&token=" . urlencode($verificationToken);

            $mail->Body = "
                <html>
                    <head><title>Email Verification</title></head>
                    <body>
                        <h2>Welcome to Wafra, {$userName}!</h2>
                        <p>Please click the link below to verify your email address:</p>
                        <p><a href='{$verificationLink}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
                        <p>If the button doesn't work, copy and paste this link into your browser:</p>
                        <p>{$verificationLink}</p>
                        <p>This link will expire in 24 hours.</p>
                        <p>If you didn't create an account, please ignore this email.</p>
                    </body>
                </html>
            ";

            $mail->AltBody = "Welcome to Wafra, {$userName}!\n\nUse the link below to verify your email (valid for 24 hours):\n{$verificationLink}";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailUtility sendVerificationEmail error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendEmailChangeVerification($toEmail, $verificationToken, $userName) {
        try {
            $mail = $this->bootstrapMailer();
            $mail->addAddress($toEmail, $userName);
            $mail->Subject = 'Verify Your New Email Address - Wafra';

            // Ensure appUrl includes the full path to wafra-integration
            $appUrl = $this->appUrl;
            if (strpos($appUrl, 'wafra-integration') === false) {
                $appUrl = rtrim($appUrl, '/') . '/wafra-integration';
            }
            $verificationLink = "{$appUrl}/index.php?action=verify_email&token=" . urlencode($verificationToken);

            $mail->Body = "
                <html>
                    <head><title>Email Change Verification</title></head>
                    <body>
                        <h2>Email Address Change - Wafra</h2>
                        <p>Hello {$userName},</p>
                        <p>You have requested to change your email address. Please click the link below to verify your new email:</p>
                        <p><a href='{$verificationLink}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify New Email</a></p>
                        <p>If the button doesn't work, copy and paste this link into your browser:</p>
                        <p>{$verificationLink}</p>
                        <p>This link will expire in 24 hours.</p>
                        <p>If you didn't request this change, please ignore this email.</p>
                    </body>
                </html>
            ";

            $mail->AltBody = "You requested to change your Wafra email. Verify using the link below (valid for 24 hours):\n{$verificationLink}";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailUtility sendEmailChangeVerification error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordResetEmail($toEmail, $userName, $resetToken) {
        try {
            $mail = $this->bootstrapMailer();
            $mail->addAddress($toEmail, $userName);
            $mail->Subject = 'Reset Your Password - Wafra';

            // Ensure appUrl includes the full path to wafra-integration
            $appUrl = $this->appUrl;
            // If appUrl doesn't contain wafra-integration, add it
            if (strpos($appUrl, 'wafra-integration') === false) {
                $appUrl = rtrim($appUrl, '/') . '/wafra-integration';
            }
            $resetLink = "{$appUrl}/view/frontoffice/reset_password.php?token=" . urlencode($resetToken);

            $mail->Body = "
                <html>
                    <head><title>Password Reset</title></head>
                    <body>
                        <h2>Password Reset Request</h2>
                        <p>Hello {$userName},</p>
                        <p>We received a request to reset your password. Click the button below to set a new password:</p>
                        <p><a href='{$resetLink}' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                        <p>If the button does not work, copy and paste this link into your browser:</p>
                        <p>{$resetLink}</p>
                        <p>This link will expire in 30 minutes. If you didn't request this, please ignore the email.</p>
                    </body>
                </html>
            ";

            $mail->AltBody = "Reset your password using this link (valid for 30 minutes): {$resetLink}";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailUtility sendPasswordResetEmail error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email to admin when a new reclamation is created
     */
    public function sendNewReclamationNotificationToAdmin($reclamation, $adminEmail = 'yassineou.haddadou@gmail.com') {
        try {
            $mail = $this->bootstrapMailer();
            $mail->addAddress($adminEmail);
            $mail->Subject = 'Nouvelle Réclamation #' . $reclamation['id'] . ' - Wafra';

            $priorityBadge = '';
            if ($reclamation['priorite'] === 'Haute') {
                $priorityBadge = '<span style="background: #dc3545; color: white; padding: 5px 10px; border-radius: 5px;">🔴 Haute</span>';
            } elseif ($reclamation['priorite'] === 'Moyenne') {
                $priorityBadge = '<span style="background: #ffc107; color: #000; padding: 5px 10px; border-radius: 5px;">🟡 Moyenne</span>';
            } else {
                $priorityBadge = '<span style="background: #28a745; color: white; padding: 5px 10px; border-radius: 5px;">🟢 Basse</span>';
            }

            $appUrl = $this->appUrl;
            if (strpos($appUrl, 'wafra-integration') === false) {
                $appUrl = rtrim($appUrl, '/') . '/wafra-integration';
            }
            $adminLink = "{$appUrl}/index.php?action=dashboard&section=reclamations";

            $mail->Body = "
                <html>
                    <head>
                        <title>Nouvelle Réclamation</title>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: #f5a425; color: white; padding: 20px; text-align: center; }
                            .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                            .info-row { margin: 10px 0; }
                            .label { font-weight: bold; color: #555; }
                            .button { display: inline-block; background: #f5a425; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>🔔 Nouvelle Réclamation Reçue</h2>
                            </div>
                            <div class='content'>
                                <p>Une nouvelle réclamation a été soumise sur la plateforme Wafra.</p>
                                
                                <div class='info-row'>
                                    <span class='label'>Numéro de réclamation :</span> #{$reclamation['id']}
                                </div>
                                <div class='info-row'>
                                    <span class='label'>Nom :</span> " . htmlspecialchars($reclamation['nom']) . "
                                </div>
                                <div class='info-row'>
                                    <span class='label'>Email :</span> " . htmlspecialchars($reclamation['email']) . "
                                </div>
                                <div class='info-row'>
                                    <span class='label'>Téléphone :</span> " . htmlspecialchars($reclamation['telephone']) . "
                                </div>
                                <div class='info-row'>
                                    <span class='label'>Type :</span> " . htmlspecialchars($reclamation['type']) . "
                                </div>
                                <div class='info-row'>
                                    <span class='label'>Priorité :</span> {$priorityBadge}
                                </div>
                                <div class='info-row'>
                                    <span class='label'>Date :</span> " . date('d/m/Y à H:i', strtotime($reclamation['date_creation'])) . "
                                </div>
                                <div class='info-row'>
                                    <span class='label'>Description :</span><br>
                                    <div style='background: white; padding: 15px; border-radius: 5px; margin-top: 10px;'>
                                        " . nl2br(htmlspecialchars($reclamation['description'])) . "
                                    </div>
                                </div>
                                
                                <div style='text-align: center; margin-top: 30px;'>
                                    <a href='{$adminLink}' class='button'>Voir la réclamation</a>
                                </div>
                            </div>
                        </div>
                    </body>
                </html>
            ";

            $mail->AltBody = "Nouvelle réclamation #{$reclamation['id']}\n\n" .
                "Nom: {$reclamation['nom']}\n" .
                "Email: {$reclamation['email']}\n" .
                "Type: {$reclamation['type']}\n" .
                "Priorité: {$reclamation['priorite']}\n" .
                "Description: {$reclamation['description']}\n\n" .
                "Voir: {$adminLink}";

            $mail->send();
            error_log("✅ Email de notification envoyé à l'admin pour la réclamation #{$reclamation['id']}");
            return true;
        } catch (Exception $e) {
            error_log('EmailUtility sendNewReclamationNotificationToAdmin error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email to user when a response is added to their reclamation
     */
    public function sendReclamationResponseNotification($reclamation, $response, $userEmail) {
        try {
            $mail = $this->bootstrapMailer();
            $mail->addAddress($userEmail);
            $mail->Subject = 'Réponse à votre réclamation #' . $reclamation['id'] . ' - Wafra';

            $appUrl = $this->appUrl;
            if (strpos($appUrl, 'wafra-integration') === false) {
                $appUrl = rtrim($appUrl, '/') . '/wafra-integration';
            }
            $viewLink = "{$appUrl}/index.php?action=reclamation_view&id={$reclamation['id']}";

            $mail->Body = "
                <html>
                    <head>
                        <title>Réponse à votre réclamation</title>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                            .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                            .response-box { background: white; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0; border-radius: 5px; }
                            .button { display: inline-block; background: #f5a425; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>✅ Réponse à votre réclamation</h2>
                            </div>
                            <div class='content'>
                                <p>Bonjour " . htmlspecialchars($reclamation['nom']) . ",</p>
                                
                                <p>Nous avons le plaisir de vous informer qu'une réponse a été apportée à votre réclamation <strong>#{$reclamation['id']}</strong>.</p>
                                
                                <div class='response-box'>
                                    <h3 style='margin-top: 0; color: #28a745;'>Réponse de l'administration :</h3>
                                    <p style='white-space: pre-wrap;'>" . nl2br(htmlspecialchars($response['message'])) . "</p>
                                    <p style='color: #666; font-size: 12px; margin-top: 15px;'>
                                        Répondu le " . date('d/m/Y à H:i', strtotime($response['date_reponse'])) . "
                                    </p>
                                </div>
                                
                                <div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                                    <strong>Détails de votre réclamation :</strong><br>
                                    Type : " . htmlspecialchars($reclamation['type']) . "<br>
                                    Priorité : " . htmlspecialchars($reclamation['priorite']) . "<br>
                                    Statut : <strong style='color: #28a745;'>Répondu</strong>
                                </div>
                                
                                <div style='text-align: center; margin-top: 30px;'>
                                    <a href='{$viewLink}' class='button'>Voir ma réclamation</a>
                                </div>
                                
                                <p style='margin-top: 30px; color: #666; font-size: 12px;'>
                                    Si vous avez d'autres questions, n'hésitez pas à nous contacter.
                                </p>
                            </div>
                        </div>
                    </body>
                </html>
            ";

            $mail->AltBody = "Réponse à votre réclamation #{$reclamation['id']}\n\n" .
                "Bonjour {$reclamation['nom']},\n\n" .
                "Une réponse a été apportée à votre réclamation.\n\n" .
                "Réponse :\n{$response['message']}\n\n" .
                "Répondu le " . date('d/m/Y à H:i', strtotime($response['date_reponse'])) . "\n\n" .
                "Voir votre réclamation : {$viewLink}";

            $mail->send();
            error_log("✅ Email de réponse envoyé à {$userEmail} pour la réclamation #{$reclamation['id']}");
            return true;
        } catch (Exception $e) {
            error_log('EmailUtility sendReclamationResponseNotification error: ' . $e->getMessage());
            return false;
        }
    }
}

