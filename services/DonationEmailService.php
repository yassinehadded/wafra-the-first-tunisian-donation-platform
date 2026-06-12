<?php
/**
 * Donation Email Service
 * Handles all email notifications for donation system
 */
require_once __DIR__ . '/EmailService.php';

class DonationEmailService {
    private $emailService;
    
    public function __construct() {
        $this->emailService = new EmailService();
    }
    
    /**
     * Send email to requester when request is submitted
     */
    public function sendProcessingEmail($requesterEmail, $request, $donation) {
        if (empty($requesterEmail)) {
            return false;
        }
        
        $requesterName = is_array($request) ? ($request['requester_name'] ?? '') : $request->getRequesterName();
        $quantity = is_array($request) ? ($request['quantity'] ?? '') : $request->getQuantity();
        $category = is_array($request) ? ($request['category'] ?? '') : $request->getCategory();
        $itemTitle = $donation['title'] ?? 'your requested item';
        
        $subject = "Votre demande de donation est en cours de traitement";
        
        $body = "
        <div style='font-family:Arial, sans-serif; max-width:600px; margin:auto; border:1px solid #eee; padding:20px;'>
            <h2 style='color:#007bff;'>Demande en cours de traitement</h2>
            <p>Bonjour {$requesterName},</p>
            
            <p>Votre demande pour <strong>{$itemTitle}</strong> est maintenant en cours de traitement.</p>
            
            <p><strong>Détails:</strong><br>
            Quantité: {$quantity}<br>
            Catégorie: {$category}</p>
            
            <p>Nous vous contacterons une fois que la demande sera approuvée ou refusée.</p>
            <p>Si vous avez besoin de plus de détails, veuillez nous contacter via le site Wafra.</p>
            
            <hr>
            <p style='font-size:12px; color:#666;'>Wafra</p>
        </div>";
        
        return $this->emailService->send($requesterEmail, $subject, $body, true);
    }
    
    /**
     * Send email to requester when request is approved
     */
    public function sendApprovalEmail($requesterEmail, $request, $donation) {
        if (empty($requesterEmail)) {
            return false;
        }
        
        $requesterName = is_array($request) ? ($request['requester_name'] ?? '') : $request->getRequesterName();
        $quantity = is_array($request) ? ($request['quantity'] ?? '') : $request->getQuantity();
        $category = is_array($request) ? ($request['category'] ?? '') : $request->getCategory();
        $donationTitle = $donation['title'] ?? 'N/A';
        
        $subject = "Votre demande de donation a été approuvée !";
        
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #eee;padding:20px;'>
            <h2 style='color:#28a745;'>Approuvée ✔</h2>
            <p>Bonjour {$requesterName},</p>
            <p>Votre demande de donation pour <strong>{$donationTitle}</strong> a été approuvée.</p>
            <p>Quantité: {$quantity}<br>Catégorie: {$category}</p>
            <p>Nous vous notifierons dès que la demande sera prête.</p>
            <hr>
            <p style='font-size:12px;color:#666;'>Wafra</p>
        </div>";
        
        return $this->emailService->send($requesterEmail, $subject, $body, true);
    }
    
    /**
     * Send email to requester when request is rejected
     */
    public function sendRejectionEmail($requesterEmail, $request, $reason = '') {
        if (empty($requesterEmail)) {
            return false;
        }
        
        if (strtolower($reason) === 'other' || empty($reason)) {
            $reasonMessage = "Veuillez contacter notre site web pour plus d'informations.";
        } else {
            $reasonMessage = htmlspecialchars($reason);
        }
        
        $requesterName = is_array($request) ? ($request['requester_name'] ?? '') : $request->getRequesterName();
        $quantity = is_array($request) ? ($request['quantity'] ?? '') : $request->getQuantity();
        $category = is_array($request) ? ($request['category'] ?? '') : $request->getCategory();
        
        $subject = "Votre demande de donation a été refusée";
        
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #eee;padding:20px;'>
            <h2 style='color:#dc3545;'>Refusée ✘</h2>
            <p>Bonjour {$requesterName},</p>
            <p>Votre demande de donation pour <strong>{$category}</strong> a été refusée.</p>
            <p>Quantité: {$quantity}</p>
            <p>Raison: {$reasonMessage}</p>
            <hr>
            <p style='font-size:12px;color:#666;'>Wafra</p>
        </div>";
        
        return $this->emailService->send($requesterEmail, $subject, $body, true);
    }
    
    /**
     * Send email to admin when new donation request is made
     */
    public function sendAdminNotificationEmail($adminEmail, $request, $donation) {
        if (empty($adminEmail)) {
            return false;
        }
        
        $requesterName = is_array($request) ? ($request['requester_name'] ?? '') : $request->getRequesterName();
        $requesterEmail = is_array($request) ? ($request['email'] ?? '') : $request->getEmail();
        $donationTitle = $donation['title'] ?? 'N/A';
        $donorName = $donation['donor_name'] ?? 'N/A';
        
        $subject = "Nouvelle demande de donation - {$donationTitle}";
        
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #eee;padding:20px;'>
            <h2 style='color:#007bff;'>Nouvelle demande de donation</h2>
            <p>Une nouvelle demande de donation a été soumise.</p>
            
            <p><strong>Détails de la demande:</strong><br>
            Demandeur: {$requesterName}<br>
            Email: {$requesterEmail}<br>
            Donation: {$donationTitle}<br>
            Donateur: {$donorName}</p>
            
            <p>Veuillez vous connecter au tableau de bord pour examiner et traiter cette demande.</p>
            
            <hr>
            <p style='font-size:12px;color:#666;'>Wafra</p>
        </div>";
        
        return $this->emailService->send($adminEmail, $subject, $body, true);
    }
    
    /**
     * Send approval email with donor contact information
     */
    public function sendApprovalEmailWithDonorContact($requesterEmail, $request, $donation, $donorInfo) {
        if (empty($requesterEmail)) {
            return false;
        }
        
        $requesterName = is_array($request) ? ($request['requester_name'] ?? '') : $request->getRequesterName();
        $quantity = is_array($request) ? ($request['quantity'] ?? '') : $request->getQuantity();
        $category = is_array($request) ? ($request['category'] ?? '') : $request->getCategory();
        $donationTitle = $donation['title'] ?? 'N/A';
        
        $donorName = $donorInfo['name'] ?? 'Donateur';
        $donorEmail = $donorInfo['email'] ?? 'Non disponible';
        $donorPhone = $donorInfo['phone'] ?? 'Non disponible';
        
        $subject = "Votre demande de donation a été approuvée !";
        
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #eee;padding:20px;'>
            <h2 style='color:#28a745;'>Demande Approuvée ✔</h2>
            <p>Bonjour {$requesterName},</p>
            
            <p>Félicitations ! Votre demande de donation pour <strong>{$donationTitle}</strong> a été approuvée.</p>
            
            <div style='background:#f8f9fa;padding:15px;border-radius:5px;margin:20px 0;'>
                <p><strong>Détails de votre demande:</strong><br>
                Quantité: {$quantity}<br>
                Catégorie: {$category}</p>
            </div>
            
            <div style='background:#e7f3ff;padding:15px;border-radius:5px;margin:20px 0;border-left:4px solid #007bff;'>
                <h3 style='color:#007bff;margin-top:0;'>Coordonnées du Donateur</h3>
                <p style='margin-bottom:5px;'><strong>Nom:</strong> {$donorName}</p>
                <p style='margin-bottom:5px;'><strong>Email:</strong> <a href='mailto:{$donorEmail}'>{$donorEmail}</a></p>
                <p style='margin-bottom:0;'><strong>Téléphone:</strong> <a href='tel:{$donorPhone}'>{$donorPhone}</a></p>
            </div>
            
            <p><strong>Prochaines étapes:</strong></p>
            <ul>
                <li>Contactez le donateur aux coordonnées ci-dessus pour organiser la récupération</li>
                <li>Confirmez la date et le lieu de rencontre</li>
                <li>Merci de confirmer la réception une fois que vous avez récupéré la donation</li>
            </ul>
            
            <p style='color:#666;font-size:14px;'>Si vous avez des questions, n'hésitez pas à nous contacter via le site Wafra.</p>
            
            <hr>
            <p style='font-size:12px;color:#666;'>Wafra - Plateforme de Donations</p>
        </div>";
        
        return $this->emailService->send($requesterEmail, $subject, $body, true);
    }
}

