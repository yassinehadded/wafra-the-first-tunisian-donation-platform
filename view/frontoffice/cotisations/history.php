<?php
/**
 * Cotisation History View
 */
$pageTitle = 'Historique des Cotisations';
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
        <h2 class="mb-4"><?= $pageTitle ?></h2>
        
        <?php if (empty($cotisations)): ?>
            <div class="alert alert-info">
                Aucune cotisation dans votre historique.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Association</th>
                            <th>Montant</th>
                            <th>Période</th>
                            <th>Date de paiement</th>
                            <th>Méthode</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cotisations as $cotisation): ?>
                            <tr>
                                <td><?= htmlspecialchars($cotisation['association_name'] ?? 'N/A') ?></td>
                                <td><?= number_format($cotisation['amount'], 2) ?> TND</td>
                                <td><?= htmlspecialchars($cotisation['period'] ?? 'N/A') ?></td>
                                <td><?= $cotisation['payment_date'] ? date('d/m/Y', strtotime($cotisation['payment_date'])) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($cotisation['payment_method'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge bg-<?= $cotisation['payment_status'] === 'paid' ? 'success' : ($cotisation['payment_status'] === 'pending' ? 'warning' : 'danger') ?>">
                                        <?= htmlspecialchars(ucfirst($cotisation['payment_status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <a href="<?= $baseUrl ?>/index.php?action=cotisations" class="btn btn-secondary mt-3">
            Retour
        </a>
    </div>
    
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>

