<?php
/**
 * Report Details View (for modal)
 */
$report = $report ?? null;
$reportsForPost = $reportsForPost ?? [];

if (!$report) {
    echo '<div class="alert alert-danger">Signalement introuvable.</div>';
    exit;
}

$reasonLabels = [
    'spam' => 'Spam',
    'harassment' => 'Harcèlement',
    'hate_speech' => 'Discours de haine',
    'fake_information' => 'Fausse information',
    'inappropriate_content' => 'Contenu inapproprié',
    'other' => 'Autre'
];

$statusLabels = [
    'pending' => ['label' => 'En attente', 'class' => 'warning'],
    'reviewed' => ['label' => 'Examiné', 'class' => 'success'],
    'dismissed' => ['label' => 'Rejeté', 'class' => 'secondary']
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
    <h6>Post Signalé</h6>
    <div class="card">
        <div class="card-body">
            <h5><?= htmlspecialchars($report['post_title'] ?? 'Post supprimé') ?></h5>
            <?php if (!empty($report['post_description'])): ?>
                <p><?= htmlspecialchars(substr($report['post_description'], 0, 200)) ?><?= strlen($report['post_description']) > 200 ? '...' : '' ?></p>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/view/frontoffice/posts.php?post_id=<?= $report['id_post'] ?>" 
               target="_blank" class="btn btn-sm btn-primary">
                <i class="bi bi-box-arrow-up-right"></i> Voir le post
            </a>
        </div>
    </div>
</div>

<?php if (count($reportsForPost) > 1): ?>
<div class="mb-3">
    <h6>Autres Signalements pour ce Post (<?= count($reportsForPost) ?>)</h6>
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
                <?php foreach ($reportsForPost as $r): ?>
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
    <form method="POST" action="<?= BASE_URL ?>/index.php?action=dashboard&section=reported_posts">
        <input type="hidden" name="report_id" value="<?= $report['id_report'] ?>">
        <input type="hidden" name="update_report_status" value="1">
        
        <div class="mb-3">
            <label class="form-label">Notes Admin (optionnel)</label>
            <textarea name="admin_notes" class="form-control" rows="3" 
                      placeholder="Ajoutez des notes internes..."><?= htmlspecialchars($report['admin_notes'] ?? '') ?></textarea>
        </div>
        
        <div class="d-flex gap-2">
            <button type="submit" name="status" value="reviewed" class="btn btn-success">
                <i class="bi bi-check-circle"></i> Marquer comme examiné
            </button>
            <button type="submit" name="status" value="dismissed" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Rejeter
            </button>
        </div>
    </form>
</div>
<?php endif; ?>





