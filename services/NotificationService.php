<?php
/**
 * Notification Service
 * Handles notification creation and business logic
 */
class NotificationService {
    private $pdo;
    private $notificationModel;
    private $userModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/Notification.php';
        require_once __DIR__ . '/../models/User.php';
        $this->notificationModel = new Notification($pdo);
        $this->userModel = new User($pdo);
    }

    /**
     * Notify user about a like on their post
     */
    public function notifyPostLiked($postId, $likerId, $postOwnerId) {
        // Prevent self-notification
        if ($likerId == $postOwnerId) {
            return false;
        }

        // Check if notification already exists
        if ($this->notificationModel->exists($postOwnerId, 'post_liked', 'post', $postId, $likerId)) {
            return false;
        }

        $liker = $this->userModel->getUserByCin($likerId);
        $likerName = $liker ? trim(($liker['firstname'] ?? '') . ' ' . ($liker['lastname'] ?? '')) : 'Quelqu\'un';
        if (empty(trim($likerName))) {
            $likerName = 'Quelqu\'un';
        }

        $message = $likerName . " a aimé votre post";
        return $this->notificationModel->create($postOwnerId, 'post_liked', 'post', $postId, $message, $likerId);
    }

    /**
     * Notify user about a comment on their post
     */
    public function notifyPostCommented($postId, $commenterId, $postOwnerId) {
        if ($commenterId == $postOwnerId) {
            return false;
        }

        if ($this->notificationModel->exists($postOwnerId, 'post_commented', 'post', $postId, $commenterId)) {
            return false;
        }

        $commenter = $this->userModel->getUserByCin($commenterId);
        $commenterName = $commenter ? trim(($commenter['firstname'] ?? '') . ' ' . ($commenter['lastname'] ?? '')) : 'Quelqu\'un';
        if (empty(trim($commenterName))) {
            $commenterName = 'Quelqu\'un';
        }

        $message = $commenterName . " a commenté votre post";
        return $this->notificationModel->create($postOwnerId, 'post_commented', 'post', $postId, $message, $commenterId);
    }

    /**
     * Notify user about a reply to their comment
     */
    public function notifyCommentReplied($commentId, $replierId, $commentOwnerId, $postId) {
        if ($replierId == $commentOwnerId) {
            return false;
        }

        if ($this->notificationModel->exists($commentOwnerId, 'comment_replied', 'comment', $commentId, $replierId)) {
            return false;
        }

        $replier = $this->userModel->getUserByCin($replierId);
        $replierName = $replier ? trim(($replier['firstname'] ?? '') . ' ' . ($replier['lastname'] ?? '')) : 'Quelqu\'un';
        if (empty(trim($replierName))) {
            $replierName = 'Quelqu\'un';
        }

        $message = $replierName . " a répondu à votre commentaire";
        return $this->notificationModel->create($commentOwnerId, 'comment_replied', 'comment', $commentId, $message, $replierId);
    }

    /**
     * Notify user about post engagement milestone
     */
    public function notifyPostMilestone($postId, $postOwnerId, $milestone, $type = 'likes') {
        $message = "Votre post a atteint {$milestone} " . ($type === 'likes' ? 'j\'aime' : 'commentaires') . " !";
        
        // Only create if doesn't exist
        if ($this->notificationModel->exists($postOwnerId, 'post_milestone', 'post', $postId)) {
            return false;
        }

        return $this->notificationModel->create($postOwnerId, 'post_milestone', 'post', $postId, $message);
    }
    
    /**
     * Notify user about a new message
     */
    public function notifyNewMessage($conversationId, $senderId, $recipientId, $senderName) {
        // Prevent self-notification
        if ($senderId == $recipientId) {
            return false;
        }
        
        // Check if notification already exists (prevent duplicates)
        if ($this->notificationModel->exists($recipientId, 'new_message', 'conversation', $conversationId, $senderId)) {
            return false;
        }
        
        $message = $senderName . " vous a envoyé un message";
        return $this->notificationModel->create($recipientId, 'new_message', 'conversation', $conversationId, $message, $senderId);
    }

    /**
     * Notify user about post removal
     */
    public function notifyPostRemoved($postId, $postOwnerId, $reason = '') {
        $message = "Votre post a été supprimé par un administrateur" . ($reason ? " : " . $reason : "");
        return $this->notificationModel->create($postOwnerId, 'post_removed', 'post', $postId, $message);
    }

    /**
     * Notify admin about new reported post
     */
    public function notifyAdminPostReported($postId, $reporterId) {
        // Get all admins
        $admins = $this->getAdmins();
        
        $reporter = $this->userModel->getUserByCin($reporterId);
        $reporterName = $reporter ? trim(($reporter['firstname'] ?? '') . ' ' . ($reporter['lastname'] ?? '')) : 'Un utilisateur';
        
        $message = "Nouveau signalement de post par " . $reporterName;
        
        $count = 0;
        foreach ($admins as $admin) {
            if (!$this->notificationModel->exists($admin['cin'], 'admin_post_reported', 'post', $postId)) {
                $this->notificationModel->create($admin['cin'], 'admin_post_reported', 'post', $postId, $message, $reporterId);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Notify admin about new reported comment
     */
    public function notifyAdminCommentReported($commentId, $reporterId) {
        $admins = $this->getAdmins();
        
        $reporter = $this->userModel->getUserByCin($reporterId);
        $reporterName = $reporter ? trim(($reporter['firstname'] ?? '') . ' ' . ($reporter['lastname'] ?? '')) : 'Un utilisateur';
        
        $message = "Nouveau signalement de commentaire par " . $reporterName;
        
        $count = 0;
        foreach ($admins as $admin) {
            if (!$this->notificationModel->exists($admin['cin'], 'admin_comment_reported', 'comment', $commentId)) {
                $this->notificationModel->create($admin['cin'], 'admin_comment_reported', 'comment', $commentId, $message, $reporterId);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Notify admin about user exceeding report threshold
     */
    public function notifyAdminUserThreshold($userId, $reportCount) {
        $admins = $this->getAdmins();
        
        $user = $this->userModel->getUserByCin($userId);
        $userName = $user ? trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) : 'Un utilisateur';
        
        $message = "L'utilisateur {$userName} a dépassé le seuil de signalements ({$reportCount})";
        
        $count = 0;
        foreach ($admins as $admin) {
            if (!$this->notificationModel->exists($admin['cin'], 'admin_user_threshold', 'user', $userId)) {
                $this->notificationModel->create($admin['cin'], 'admin_user_threshold', 'user', $userId, $message, $userId);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get all admin users
     */
    private function getAdmins() {
        try {
            $sql = "SELECT cin, firstname, lastname, email FROM users WHERE role = 'admin'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting admins: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check and notify about engagement milestones
     */
    public function checkPostMilestones($postId, $postOwnerId) {
        require_once __DIR__ . '/PostLikeService.php';
        require_once __DIR__ . '/PostCommentService.php';
        
        $likeService = new PostLikeService($this->pdo);
        $commentService = new PostCommentService($this->pdo);
        
        $likesCount = $likeService->getLikeCount($postId);
        $commentsCount = $commentService->getCommentCount($postId);
        
        // Check for like milestones (10, 25, 50, 100)
        $likeMilestones = [10, 25, 50, 100];
        foreach ($likeMilestones as $milestone) {
            if ($likesCount == $milestone) {
                $this->notifyPostMilestone($postId, $postOwnerId, $milestone, 'likes');
                break; // Only notify once per milestone
            }
        }
        
        // Check for comment milestones (5, 10, 25)
        $commentMilestones = [5, 10, 25];
        foreach ($commentMilestones as $milestone) {
            if ($commentsCount == $milestone) {
                $this->notifyPostMilestone($postId, $postOwnerId, $milestone, 'comments');
                break;
            }
        }
    }
}

