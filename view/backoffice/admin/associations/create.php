<?php
/**
 * Admin Create Association View
 */
?>
<div class="page-heading">
    <h3>Ajouter une Association</h3>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/index.php?action=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/index.php?action=dashboard&section=associations">Associations</a></li>
            <li class="breadcrumb-item active">Ajouter</li>
        </ol>
    </nav>
</div>

<?php if (isset($errorMessage) && $errorMessage): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h4>Nouvelle Association</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= $baseUrl ?>/index.php?action=dashboard&section=associations&section_action=create">
            <input type="hidden" name="action" value="admin_association_create">
            <input type="hidden" name="section" value="associations">
            <input type="hidden" name="section_action" value="create">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required maxlength="255">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required maxlength="255">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" required maxlength="20">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                    <input type="text" name="category" class="form-control" required maxlength="100">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Adresse <span class="text-danger">*</span></label>
                <input type="text" name="address" class="form-control" required maxlength="255">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Statut <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="Active" selected>Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Créer l'association
                </button>
                <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=associations" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

