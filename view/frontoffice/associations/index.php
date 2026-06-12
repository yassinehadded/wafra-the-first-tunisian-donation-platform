<?php
/**
 * Associations List View
 */
$pageTitle = 'Associations';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Wafra</title>
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
        .association-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .association-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .association-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .association-description {
            color: #666;
            margin-bottom: 15px;
        }
        .association-info {
            font-size: 14px;
            color: #888;
            margin-bottom: 5px;
        }
        .association-info i {
            margin-right: 5px;
            color: #667eea;
        }
        .btn-join {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-join:hover {
            background: #5568d3;
        }
        .btn-member {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            pointer-events: none;
        }
        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            background: #667eea;
            color: white;
            margin-right: 10px;
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
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Associations</h2>
                
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
                
                <?php if (empty($associations)): ?>
                    <div class="alert alert-info">
                        Aucune association active disponible pour le moment.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($associations as $association): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="association-card">
                                    <div class="association-title">
                                        <?= htmlspecialchars($association['name']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <span class="category-badge"><?= htmlspecialchars($association['category'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="association-description">
                                        <?= htmlspecialchars(substr($association['description'] ?? '', 0, 100)) ?>
                                        <?= strlen($association['description'] ?? '') > 100 ? '...' : '' ?>
                                    </div>
                                    <div class="association-info">
                                        <i class="fa fa-map-marker"></i> <?= htmlspecialchars($association['address'] ?? 'N/A') ?>
                                    </div>
                                    <div class="association-info">
                                        <i class="fa fa-phone"></i> <?= htmlspecialchars($association['phone'] ?? 'N/A') ?>
                                    </div>
                                    <div class="association-info">
                                        <i class="fa fa-envelope"></i> <?= htmlspecialchars($association['email'] ?? 'N/A') ?>
                                    </div>
                                    <div class="mt-3">
                                        <a href="<?= $baseUrl ?>/index.php?action=association_show&id=<?= $association['id'] ?>" class="btn btn-sm btn-primary">
                                            Voir détails
                                        </a>
                                        <?php if (in_array($association['id'], $userAssociationIds)): ?>
                                            <span class="btn btn-sm btn-member">Membre</span>
                                        <?php else: ?>
                                            <a href="<?= $baseUrl ?>/index.php?action=association_join&id=<?= $association['id'] ?>" class="btn btn-sm btn-join">
                                                Rejoindre
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>

