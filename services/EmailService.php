<?php
/**
 * Email Service
 * Wrapper for EmailUtility to provide email sending functionality
 */
require_once __DIR__ . '/../models/EmailUtility.php';

// PHPMailer namespace (will be loaded when needed)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService {
    private $emailUtility;

    public function __construct() {
        $this->emailUtility = new EmailUtility();
    }

    public function send($to, $subject, $message, $isHtml = false) {
        try {
            // Check if PHPMailer exists
            $phpmailerPath = __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
            if (!file_exists($phpmailerPath)) {
                error_log("PHPMailer not found at: $phpmailerPath - Email sending disabled");
                return false; // Return false instead of throwing error
            }
            
            require_once $phpmailerPath;
            require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
            require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
            
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USERNAME') ?: '';
            $mail->Password = getenv('SMTP_PASSWORD') ?: '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
            $mail->isHTML($isHtml);
            $mail->setFrom(getenv('MAIL_FROM_ADDRESS') ?: 'noreply@wafra.com', getenv('MAIL_FROM_NAME') ?: 'Wafra');
            
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $message;
            if (!$isHtml) {
                $mail->AltBody = strip_tags($message);
            }
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService send error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendVerificationEmail($toEmail, $verificationToken, $userName) {
        return $this->emailUtility->sendVerificationEmail($toEmail, $verificationToken, $userName);
    }

    public function sendEmailChangeVerification($toEmail, $verificationToken, $userName) {
        return $this->emailUtility->sendEmailChangeVerification($toEmail, $verificationToken, $userName);
    }

    public function sendPasswordResetEmail($toEmail, $userName, $resetToken) {
        return $this->emailUtility->sendPasswordResetEmail($toEmail, $userName, $resetToken);
    }

    public function sendNewReclamationNotificationToAdmin($reclamation, $adminEmail = 'yassineou.haddadou@gmail.com') {
        return $this->emailUtility->sendNewReclamationNotificationToAdmin($reclamation, $adminEmail);
    }

    public function sendReclamationResponseNotification($reclamation, $response, $userEmail) {
        return $this->emailUtility->sendReclamationResponseNotification($reclamation, $response, $userEmail);
    }
}

