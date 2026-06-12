<?php
/**
 * Association Details View
 */
$pageTitle = 'Détails Association';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($association['name']) ?> - Wafra</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/fontawesome.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar-enhanced.css">
    <style>
        body {
            padding-top: 80px;
            background-color: #f5f5f5;
        }
        body.no-sidebar {
            padding-left: 0 !important;
        }
        body.no-sidebar .top-bar {
            left: 0 !important;
            width: 100% !important;
        }
        .association-header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .association-details {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .members-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="no-sidebar">
    <!-- Top Bar -->
    <div class="top-bar" style="position: fixed; top: 0; left: 0; right: 0; background: #1a1a1a; padding: 15px 20px; z-index: 1000; display: flex; justify-content: space-between; align-items: center;">
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" style="text-decoration: none;">
            <h4 style="margin: 0; color: #ffd700; font-weight: 700;">Wafra</h4>
        </a>
        <div>
            <a href="<?= $baseUrl ?>/index.php?action=associations" style="color: white; margin-right: 20px; text-decoration: none;">Associations</a>
            <a href="<?= $baseUrl ?>/index.php?action=cotisations" style="color: white; margin-right: 20px; text-decoration: none;">Cotisations</a>
            <a href="<?= $baseUrl ?>/view/frontoffice/index.php" style="color: white; text-decoration: none;">Accueil</a>
        </div>
    </div>
    
    <div class="container mt-4">
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="association-header">
            <h1><?= htmlspecialchars($association['name']) ?></h1>
            <p class="text-muted"><?= htmlspecialchars($association['category'] ?? 'N/A') ?></p>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="association-details">
                    <h3>Description</h3>
                    <p><?= nl2br(htmlspecialchars($association['description'] ?? 'Aucune description disponible.')) ?></p>
                    
                    <h4 class="mt-4">Informations de contact</h4>
                    <p><i class="fa fa-map-marker"></i> <strong>Adresse:</strong> <?= htmlspecialchars($association['address'] ?? 'N/A') ?></p>
                    <p><i class="fa fa-phone"></i> <strong>Téléphone:</strong> <?= htmlspecialchars($association['phone'] ?? 'N/A') ?></p>
                    <p><i class="fa fa-envelope"></i> <strong>Email:</strong> <?= htmlspecialchars($association['email'] ?? 'N/A') ?></p>
                    <p><i class="fa fa-info-circle"></i> <strong>Statut:</strong> <?= htmlspecialchars($association['status'] ?? 'N/A') ?></p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="association-details">
                    <h4>Actions</h4>
                    <?php if ($isMember): ?>
                        <p class="text-success"><i class="fa fa-check-circle"></i> Vous êtes membre de cette association</p>
                        <a href="<?= $baseUrl ?>/index.php?action=cotisations&association_id=<?= $association['id'] ?>" class="btn btn-primary w-100 mb-2">
                            Payer cotisation
                        </a>
                    <?php else: ?>
                        <a href="<?= $baseUrl ?>/index.php?action=association_join&id=<?= $association['id'] ?>" class="btn btn-success w-100">
                            Rejoindre l'association
                        </a>
                    <?php endif; ?>
                    <a href="<?= $baseUrl ?>/index.php?action=associations" class="btn btn-secondary w-100 mt-2">
                        Retour à la liste
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($isMember && !empty($members)): ?>
            <div class="members-list mt-4">
                <h3>Membres (<?= count($members) ?>)</h3>
                <div class="row">
                    <?php foreach ($members as $member): ?>
                        <div class="col-md-4 mb-2">
                            <div class="card">
                                <div class="card-body">
                                    <h6><?= htmlspecialchars(($member['firstname'] ?? '') . ' ' . ($member['lastname'] ?? '')) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($member['email'] ?? 'N/A') ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>

