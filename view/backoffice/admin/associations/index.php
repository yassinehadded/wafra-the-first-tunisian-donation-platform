<?php
/**
 * Admin Associations List View
 */
?>
<div class="page-heading">
    <h3>Gestion des Associations</h3>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/index.php?action=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Associations</li>
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

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Liste des Associations</h4>
        <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=associations&section_action=create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Ajouter une Association
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($associations)): ?>
            <div class="alert alert-info">Aucune association trouvée.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($associations as $association): ?>
                            <tr>
                                <td><?= $association['id'] ?></td>
                                <td><?= htmlspecialchars($association['name']) ?></td>
                                <td><?= htmlspecialchars($association['email']) ?></td>
                                <td><?= htmlspecialchars($association['phone'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($association['category'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge bg-<?= strtolower($association['status']) === 'active' ? 'success' : 'secondary' ?>">
                                        <?= htmlspecialchars($association['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=associations&section_action=update&id=<?= $association['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=associations&section_action=members&id=<?= $association['id'] ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-people"></i>
                                    </a>
                                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=associations&section_action=delete&id=<?= $association['id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette association ?')">
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
