<?php
/**
 * Admin Association Members View
 */
?>
<div class="page-heading">
    <h3>Membres de l'Association: <?= htmlspecialchars($association['name']) ?></h3>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/index.php?action=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/index.php?action=dashboard&section=associations">Associations</a></li>
            <li class="breadcrumb-item active">Membres</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header">
        <h4>Liste des Membres</h4>
    </div>
    <div class="card-body">
        <?php if (empty($members)): ?>
            <div class="alert alert-info">Aucun membre dans cette association.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Date d'adhésion</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?= htmlspecialchars(($member['firstname'] ?? '') . ' ' . ($member['lastname'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($member['email'] ?? 'N/A') ?></td>
                                <td><?= $member['joined_at'] ? date('d/m/Y', strtotime($member['joined_at'])) : 'N/A' ?></td>
                                <td>
                                    <span class="badge bg-<?= ($member['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                                        <?= htmlspecialchars(ucfirst($member['status'] ?? 'N/A')) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=associations" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>
</div>



