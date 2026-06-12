<?php
/**
 * Comment Report Details View (for modal)
 */
$report = $report ?? null;
$reportsForComment = $reportsForComment ?? [];

if (!$report) {
    echo '<div class="alert alert-danger">Signalement introuvable.</div>';
    exit;
}

$reasonLabels = [
    'spam' => 'Spam',
    'harassment' => 'Harcèlement / Haine',
    'inappropriate_content' => 'Contenu inapproprié',
    'other' => 'Autre'
];

$statusLabels = [
    'pending' => ['label' => 'En attente', 'class' => 'warning'],
    'reviewed' => ['label' => 'Examiné', 'class' => 'success'],
    'resolved' => ['label' => 'Résolu', 'class' => 'info']
];
?>

<div class="row">
    <div class="col-md-6">
        <h6>Informations du Signalement</h6>
        <table class="table table-sm">
            <tr>
                <th>ID:</th>
                <td><?= $report['id_report'] ?></td>
            </tr>
            <tr>
                <th>Date:</th>
                <td><?= date('d/m/Y H:i:s', strtotime($report['date_report'])) ?></td>
            </tr>
            <tr>
                <th>Statut:</th>
                <td>
                    <?php $statusInfo = $statusLabels[$report['status']] ?? $statusLabels['pending']; ?>
                    <span class="badge bg-<?= $statusInfo['class'] ?>"><?= $statusInfo['label'] ?></span>
                </td>
            </tr>
            <tr>
                <th>Raison:</th>
                <td><span class="badge bg-info"><?= $reasonLabels[$report['reason']] ?? 'Autre' ?></span></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>Signalé par</h6>
        <table class="table table-sm">
            <tr>
                <th>Nom:</th>
                <td><?= htmlspecialchars(($report['firstname'] ?? '') . ' ' . ($report['lastname'] ?? 'Utilisateur')) ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?= htmlspecialchars($report['reporter_email'] ?? 'N/A') ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if (!empty($report['description'])): ?>
<div class="mb-3">
    <h6>Description</h6>
    <div class="alert alert-light">
        <?= nl2br(htmlspecialchars($report['description'])) ?>
    </div>
</div>
<?php endif; ?>

<div class="mb-3">
    <h6>Commentaire Signalé</h6>
    <div class="card">
        <div class="card-body">
            <p><?= nl2br(htmlspecialchars($report['comment_text'] ?? 'Commentaire supprimé')) ?></p>
            <a href="javascript:void(0)" 
               onclick="viewPostInModal(<?= $report['id_post'] ?? 0 ?>)"
               class="btn btn-sm btn-primary">
                <i class="bi bi-box-arrow-up-right"></i> Voir le post
            </a>
        </div>
    </div>
</div>

<?php if (count($reportsForComment) > 1): ?>
<div class="mb-3">
    <h6>Autres Signalements pour ce Commentaire (<?= count($reportsForComment) ?>)</h6>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Signalé par</th>
                    <th>Raison</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportsForComment as $r): ?>
                    <?php if ($r['id_report'] != $report['id_report']): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($r['date_report'])) ?></td>
                            <td><?= htmlspecialchars(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? 'Utilisateur')) ?></td>
                            <td><span class="badge bg-info"><?= $reasonLabels[$r['reason']] ?? 'Autre' ?></span></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($report['status'] === 'pending'): ?>
<div class="mt-3">
    <form method="POST" action="<?= BASE_URL ?>/index.php?action=dashboard&section=comment_reports">
        <input type="hidden" name="report_id" value="<?= $report['id_report'] ?>">
        <input type="hidden" name="update_report_status" value="1">
        
        <div class="mb-3">
            <label class="form-label">Notes Admin (optionnel)</label>
            <textarea name="admin_notes" class="form-control" rows="3" 
                      placeholder="Ajoutez des notes internes..."><?= htmlspecialchars($report['admin_notes'] ?? '') ?></textarea>
        </div>
        
        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" name="status" value="reviewed" class="btn btn-success">
                <i class="bi bi-check-circle"></i> Marquer comme examiné
            </button>
            <button type="submit" name="status" value="resolved" class="btn btn-info">
                <i class="bi bi-check2-all"></i> Marquer comme résolu
            </button>
            <a href="<?= BASE_URL ?>/index.php?action=dashboard&section=comment_reports&delete_comment=<?= $report['id_comment'] ?>" 
               class="btn btn-danger"
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')">
                <i class="bi bi-trash"></i> Supprimer le commentaire
            </a>
        </div>
    </form>
</div>
<?php endif; ?>





