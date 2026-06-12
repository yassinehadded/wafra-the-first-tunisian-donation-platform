<?php
/**
 * Cotisations List View
 */
$pageTitle = 'Mes Cotisations';
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
        .cotisation-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-paid { background: #28a745; color: white; }
        .status-overdue { background: #dc3545; color: white; }
        .status-cancelled { background: #6c757d; color: white; }
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
                <h2 class="mb-4">Mes Cotisations</h2>
                
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
                
                <?php if (empty($userAssociations)): ?>
                    <div class="alert alert-info">
                        Vous n'êtes membre d'aucune association. <a href="<?= $baseUrl ?>/index.php?action=associations">Rejoignez une association</a> pour payer des cotisations.
                    </div>
                <?php else: ?>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4>Payer une cotisation</h4>
                            <form method="POST" action="<?= $baseUrl ?>/index.php?action=cotisation_pay">
                                <div class="row">
                                    <div class="col-md-4">
                                        <select name="association_id" class="form-control" required>
                                            <option value="">Sélectionner une association</option>
                                            <?php foreach ($userAssociations as $assoc): ?>
                                                <option value="<?= $assoc['id'] ?>"><?= htmlspecialchars($assoc['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="amount" class="form-control" placeholder="Montant" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="period" class="form-control" required>
                                            <option value="monthly">Mensuel</option>
                                            <option value="yearly">Annuel</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="payment_method" class="form-control" required>
                                            <option value="online">En ligne</option>
                                            <option value="bank_transfer">Virement</option>
                                            <option value="cash">Espèces</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Payer</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <h4 class="mt-4">Historique des cotisations</h4>
                    <?php if (empty($cotisations)): ?>
                        <div class="alert alert-info">
                            Aucune cotisation enregistrée.
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
                                            <td>
                                                <span class="status-badge status-<?= $cotisation['payment_status'] ?>">
                                                    <?= htmlspecialchars(ucfirst($cotisation['payment_status'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>

