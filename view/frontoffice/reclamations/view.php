<?php
/**
 * View Single Reclamation - Front Office
 */
$pageTitle = 'Réclamation #' . $reclamation['id'] . ' - Wafra';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link href="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/fontawesome.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar-enhanced.css">
    
    <!-- Chatbot CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/components/chatbot.css">
    
    <style>
        body {
            padding-top: 80px;
            background: #f8f9fa;
        }
        .reclamation-detail {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .detail-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-en-attente { background: #6c757d; color: white; }
        .status-en-cours { background: #17a2b8; color: white; }
        .status-repondu { background: #28a745; color: white; }
        .priority-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .priority-haute { background: #dc3545; color: white; }
        .priority-moyenne { background: #ffc107; color: #000; }
        .priority-basse { background: #28a745; color: white; }
        .response-card {
            background: #e7f3ff;
            border-left: 4px solid #17a2b8;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body class="no-sidebar">

<!-- Top Bar -->
<div class="top-bar">
    <div class="top-bar-left">
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" style="text-decoration: none;">
            <h4 style="margin: 0; color: #ffd700; font-weight: 700;">Wafra</h4>
        </a>
    </div>
    <div class="top-bar-right">
        <div class="user-info">
            <i class="fa fa-user-circle" style="font-size: 24px; color: #f5a425;"></i>
            <span class="user-name"><?= htmlspecialchars($_SESSION['firstname'] ?? '') . ' ' . htmlspecialchars($_SESSION['lastname'] ?? '') ?></span>
        </div>
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" class="profile-link" style="margin-right: 10px;">
            <i class="fa fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/posts.php" class="profile-link">
            <i class="fa fa-comments"></i>
            <span>Posts</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/messages.php" class="profile-link">
            <i class="fa fa-envelope"></i>
            <span>Messages</span>
        </a>
        <a href="<?= $baseUrl ?>/index.php?action=reclamations" class="profile-link" style="background: rgba(245, 164, 37, 0.2);">
            <i class="fa fa-exclamation-circle"></i>
            <span>Réclamations</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/profile.php" class="profile-link">
            <i class="fa fa-user"></i>
            <span>Profil</span>
        </a>
        <a href="<?= $baseUrl ?>/index.php?action=logout" class="logout-link">
            <i class="fa fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</div>

<div class="reclamation-detail">
        <div class="detail-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Réclamation #<?= $reclamation['id'] ?></h2>
                <div>
                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $reclamation['statut'])) ?>">
                        <?= htmlspecialchars($reclamation['statut']) ?>
                    </span>
                    <span class="priority-badge priority-<?= strtolower($reclamation['priorite']) ?> ml-2">
                        <?= htmlspecialchars($reclamation['priorite']) ?>
                    </span>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Nom:</strong> <?= htmlspecialchars($reclamation['nom']) ?>
                </div>
                <div class="col-md-6">
                    <strong>Email:</strong> <?= htmlspecialchars($reclamation['email']) ?>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Téléphone:</strong> <?= htmlspecialchars($reclamation['telephone']) ?>
                </div>
                <div class="col-md-6">
                    <strong>Type:</strong> <?= htmlspecialchars($reclamation['type']) ?>
                </div>
            </div>
            
            <div class="mb-3">
                <strong>Date de création:</strong> 
                <?= date('d/m/Y à H:i', strtotime($reclamation['date_creation'])) ?>
            </div>
            
            <div class="mb-3">
                <strong>Description:</strong>
                <div class="mt-2 p-3" style="background: #f8f9fa; border-radius: 8px;">
                    <?= nl2br(htmlspecialchars($reclamation['description'])) ?>
                </div>
            </div>
            
            <?php if (!empty($responses)): ?>
            <div class="response-card">
                <h5><i class="fa fa-reply"></i> Réponse de l'administration</h5>
                <?php foreach ($responses as $response): ?>
                <div class="mb-3">
                    <p><?= nl2br(htmlspecialchars($response['message'])) ?></p>
                    <small class="text-muted">
                        Répondu le <?= date('d/m/Y à H:i', strtotime($response['date_reponse'])) ?>
                        <?php if ($response['admin_firstname'] || $response['admin_lastname']): ?>
                        par <?= htmlspecialchars(trim(($response['admin_firstname'] ?? '') . ' ' . ($response['admin_lastname'] ?? ''))) ?>
                        <?php endif; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="<?= $baseUrl ?>/index.php?action=reclamations" class="btn btn-primary">
                    <i class="fa fa-arrow-left"></i> Retour à la liste
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chatbot JS -->
    <script src="<?= $baseUrl ?>/view/frontoffice/assets/js/chatbot.js"></script>
    
    <?php 
    // Pass $baseUrl to chatbot component
    $baseUrl = $baseUrl ?? (defined('BASE_URL') ? BASE_URL : '');
    $chatbotPath = __DIR__ . '/../components/chatbot.php';
    if (file_exists($chatbotPath)) {
        require_once $chatbotPath;
    }
    ?>
</body>
</html>

