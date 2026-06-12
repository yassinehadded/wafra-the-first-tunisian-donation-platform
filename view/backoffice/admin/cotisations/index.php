<?php
/**
 * Admin Cotisations List View
 */
?>
<div class="page-heading">
    <h3>Gestion des Cotisations</h3>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/index.php?action=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Cotisations</li>
        </ol>
    </nav>
</div>

<?php if ($successMessage): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($errorMessage): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= $baseUrl ?>/index.php?action=dashboard&section=cotisations" class="row g-3">
            <input type="hidden" name="action" value="dashboard">
            <input type="hidden" name="section" value="cotisations">
            
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    <option value="pending" <?= (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : '' ?>>En attente</option>
                    <option value="paid" <?= (isset($_GET['status']) && $_GET['status'] === 'paid') ? 'selected' : '' ?>>Payé</option>
                    <option value="overdue" <?= (isset($_GET['status']) && $_GET['status'] === 'overdue') ? 'selected' : '' ?>>En retard</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Association</label>
                <select name="association_id" class="form-select">
                    <option value="">Toutes</option>
                    <?php foreach ($associations as $assoc): ?>
                        <option value="<?= $assoc['id'] ?>" <?= (isset($_GET['association_id']) && $_GET['association_id'] == $assoc['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($assoc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4>Liste des Cotisations</h4>
    </div>
    <div class="card-body">
        <?php if (empty($cotisations)): ?>
            <div class="alert alert-info">Aucune cotisation trouvée.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Association</th>
                            <th>Membre</th>
                            <th>Montant</th>
                            <th>Période</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cotisations as $cotisation): ?>
                            <tr>
                                <td><?= $cotisation['id'] ?></td>
                                <td><?= htmlspecialchars($cotisation['association_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(($cotisation['firstname'] ?? '') . ' ' . ($cotisation['lastname'] ?? '')) ?></td>
                                <td><?= number_format($cotisation['amount'], 2) ?> TND</td>
                                <td><?= htmlspecialchars($cotisation['period'] ?? 'N/A') ?></td>
                                <td><?= $cotisation['payment_date'] ? date('d/m/Y', strtotime($cotisation['payment_date'])) : 'N/A' ?></td>
                                <td>
                                    <span class="badge bg-<?= $cotisation['payment_status'] === 'paid' ? 'success' : ($cotisation['payment_status'] === 'pending' ? 'warning' : 'danger') ?>">
                                        <?= htmlspecialchars(ucfirst($cotisation['payment_status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($cotisation['payment_status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-success" onclick="validatePayment(<?= $cotisation['id'] ?>)">
                                            <i class="bi bi-check"></i> Valider
                                        </button>
                                    <?php endif; ?>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=cotisations&section_action=delete&id=<?= $cotisation['id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette cotisation ?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function validatePayment(id) {
    if (confirm('Valider ce paiement ?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= $baseUrl ?>/index.php?action=dashboard&section=cotisations&section_action=validate&id=' + id;
        
        var statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'payment_status';
        statusInput.value = 'paid';
        form.appendChild(statusInput);
        
        var dateInput = document.createElement('input');
        dateInput.type = 'hidden';
        dateInput.name = 'payment_date';
        dateInput.value = '<?= date('Y-m-d') ?>';
        form.appendChild(dateInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

