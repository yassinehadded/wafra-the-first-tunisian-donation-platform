<?php
/**
 * Admin Donations View
 * List all donations with filters and actions
 */
?>
<div class="page-heading">
    <h3>Gestion des Donations</h3>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/index.php?action=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Donations</li>
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

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body">
                <h3><?= $stats['total'] ?></h3>
                <p class="mb-0"><i class="bi bi-inbox"></i> Total Donations</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <div class="card-body">
                <h3><?= $stats['active'] ?></h3>
                <p class="mb-0"><i class="bi bi-check-circle"></i> Actives</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
            <div class="card-body">
                <h3><?= $stats['fulfilled'] ?></h3>
                <p class="mb-0"><i class="bi bi-check2-all"></i> Remplies</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <div class="card-body">
        <form method="GET" action="<?= $baseUrl ?>/index.php?action=dashboard&section=donations" class="row g-3">
            <input type="hidden" name="action" value="dashboard">
            <input type="hidden" name="section" value="donations">
            
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select name="status" id="filter-status" class="form-select" onchange="applyFilters()">
                    <option value="">Tous</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="fulfilled" <?= ($filters['status'] ?? '') === 'fulfilled' ? 'selected' : '' ?>>Remplie</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Catégorie</label>
                <select name="category" id="filter-category" class="form-select" onchange="applyFilters()">
                    <option value="">Toutes</option>
                    <option value="Books" <?= ($filters['category'] ?? '') === 'Books' ? 'selected' : '' ?>>Livres</option>
                    <option value="Clothing" <?= ($filters['category'] ?? '') === 'Clothing' ? 'selected' : '' ?>>Vêtements</option>
                    <option value="Electronics" <?= ($filters['category'] ?? '') === 'Electronics' ? 'selected' : '' ?>>Électronique</option>
                    <option value="Food" <?= ($filters['category'] ?? '') === 'Food' ? 'selected' : '' ?>>Nourriture</option>
                    <option value="Other" <?= ($filters['category'] ?? '') === 'Other' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Rechercher</label>
                <div class="input-group">
                    <input type="text" name="search" id="search-input" class="form-control" 
                           placeholder="Titre, description, donateur..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                           onkeyup="debounceSearch()">
                    <button type="button" onclick="applyFilters()" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=donations" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabs for Donations and Requests -->
<ul class="nav nav-tabs mb-3" id="donationTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="donations-tab" data-bs-toggle="tab" data-bs-target="#donations" type="button" role="tab">
            <i class="bi bi-gift"></i> Donations
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">
            <i class="bi bi-hand-index"></i> Demandes
            <?php if (isset($pendingRequestsCount) && $pendingRequestsCount > 0): ?>
                <span class="badge bg-danger ms-2"><?= $pendingRequestsCount ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content" id="donationTabsContent">
    <!-- Donations Tab -->
    <div class="tab-pane fade show active" id="donations" role="tabpanel">
<!-- Donations Table -->
<div class="card">
    <div class="card-header">
        <h4>Liste des Donations</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Donateur</th>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Quantité</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="donations-tbody">
                    <?php if (empty($donations)): ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <p class="text-muted">Aucune donation trouvée</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($donations as $donation): ?>
                    <tr>
                        <td>#<?= $donation['id'] ?></td>
                        <td>
                            <?php if ($donation['user_firstname'] || $donation['user_lastname']): ?>
                                <?= htmlspecialchars(trim(($donation['user_firstname'] ?? '') . ' ' . ($donation['user_lastname'] ?? ''))) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($donation['donor_name']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($donation['title']) ?></td>
                        <td><?= htmlspecialchars($donation['category']) ?></td>
                        <td><?= $donation['quantity'] ?></td>
                        <td>
                            <span class="badge bg-<?= $donation['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= htmlspecialchars($donation['status']) === 'active' ? 'Active' : 'Remplie' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($donation['date'])) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button onclick="updateStatus(<?= $donation['id'] ?>, '<?= $donation['status'] ?>')" 
                                        class="btn btn-warning" title="Changer statut">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button onclick="updateQuantity(<?= $donation['id'] ?>, <?= $donation['quantity'] ?>)" 
                                        class="btn btn-info" title="Modifier quantité">
                                    <i class="bi bi-123"></i>
                                </button>
                                <button onclick="deleteDonation(<?= $donation['id'] ?>)" class="btn btn-danger" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    </div>
    
    <!-- Requests Tab -->
    <div class="tab-pane fade" id="requests" role="tabpanel">
<!-- Donation Requests Table -->
<div class="card">
    <div class="card-header">
        <h4>Demandes de Donations</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Demandeur</th>
                        <th>Donation</th>
                        <th>Donateur</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="requests-tbody">
                    <?php if (empty($allRequests)): ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <p class="text-muted">Aucune demande trouvée</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($allRequests as $request): ?>
                    <tr>
                        <td>#<?= $request['id'] ?></td>
                        <td>
                            <?php if ($request['requester_firstname'] || $request['requester_lastname']): ?>
                                <?= htmlspecialchars(trim(($request['requester_firstname'] ?? '') . ' ' . ($request['requester_lastname'] ?? ''))) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($request['requester_name'] ?? 'Utilisateur') ?>
                            <?php endif; ?>
                            <br><small class="text-muted"><?= htmlspecialchars($request['requester_email'] ?? $request['email'] ?? '') ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($request['donation_title'] ?? 'N/A') ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($request['donation_category'] ?? '') ?></small>
                        </td>
                        <td>
                            <?php if ($request['donor_firstname'] || $request['donor_lastname']): ?>
                                <?= htmlspecialchars(trim(($request['donor_firstname'] ?? '') . ' ' . ($request['donor_lastname'] ?? ''))) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($request['donor_name'] ?? 'N/A') ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($request['message'])): ?>
                                <span title="<?= htmlspecialchars($request['message']) ?>">
                                    <?= htmlspecialchars(mb_substr($request['message'], 0, 50)) ?><?= mb_strlen($request['message']) > 50 ? '...' : '' ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Aucun message</span>
                            <?php endif; ?>
                        </td>
                        <td><?= isset($request['request_date']) ? date('d/m/Y', strtotime($request['request_date'])) : '' ?></td>
                        <td>
                            <span class="badge bg-<?= ($request['status'] ?? 'pending') === 'approved' ? 'success' : (($request['status'] ?? 'pending') === 'denied' ? 'danger' : 'warning') ?>">
                                <?= ($request['status'] ?? 'pending') === 'approved' ? 'Approuvée' : (($request['status'] ?? 'pending') === 'denied' ? 'Refusée' : 'En attente') ?>
                            </span>
                        </td>
                        <td>
                            <?php if (($request['status'] ?? 'pending') === 'pending'): ?>
                            <div class="btn-group btn-group-sm">
                                <button onclick="approveRequest(<?= $request['id'] ?>, <?= $request['donation_id'] ?>)" 
                                        class="btn btn-success" title="Accepter">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                <button onclick="rejectRequest(<?= $request['id'] ?>, <?= $request['donation_id'] ?>)" 
                                        class="btn btn-danger" title="Refuser">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Traité</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    </div>
</div>

<!-- Modal: Update Status -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changer le Statut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusDonationId">
                <div class="mb-3">
                    <label class="form-label">Nouveau Statut</label>
                    <select id="newStatus" class="form-select">
                        <option value="active">Active</option>
                        <option value="fulfilled">Remplie</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmUpdateStatus()">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Update Quantity -->
<div class="modal fade" id="quantityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la Quantité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="quantityDonationId">
                <div class="mb-3">
                    <label class="form-label">Nouvelle Quantité</label>
                    <input type="number" id="newQuantity" class="form-control" min="0" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmUpdateQuantity()">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<script>
const baseUrl = '<?= $baseUrl ?>';

function updateStatus(id, currentStatus) {
    document.getElementById('statusDonationId').value = id;
    document.getElementById('newStatus').value = currentStatus;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function confirmUpdateStatus() {
    const id = document.getElementById('statusDonationId').value;
    const status = document.getElementById('newStatus').value;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', status);
    
    fetch(baseUrl + '/index.php?action=admin_donation_update_status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion');
    });
}

function updateQuantity(id, currentQuantity) {
    document.getElementById('quantityDonationId').value = id;
    document.getElementById('newQuantity').value = currentQuantity;
    new bootstrap.Modal(document.getElementById('quantityModal')).show();
}

function confirmUpdateQuantity() {
    const id = document.getElementById('quantityDonationId').value;
    const quantity = document.getElementById('newQuantity').value;
    
    if (quantity < 0) {
        alert('La quantité doit être positive');
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('quantity', quantity);
    
    fetch(baseUrl + '/index.php?action=admin_donation_update_quantity', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion');
    });
}

function deleteDonation(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette donation ? Cette action est irréversible.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch(baseUrl + '/index.php?action=admin_donation_delete&id=' + id, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // If not JSON, read as text
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid response format');
                }
            });
        }
    })
    .then(data => {
        if (data && data.success) {
            alert('Donation supprimée avec succès !');
            location.reload();
        } else {
            alert('Erreur: ' + (data?.error || 'Erreur lors de la suppression'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion. Veuillez réessayer.');
    });
}

let searchTimeout;
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
}

function applyFilters() {
    const status = document.getElementById('filter-status').value;
    const category = document.getElementById('filter-category').value;
    const search = document.getElementById('search-input').value.trim();
    
    let url = baseUrl + '/index.php?action=dashboard&section=donations';
    if (status) url += '&status=' + encodeURIComponent(status);
    if (category) url += '&category=' + encodeURIComponent(category);
    if (search) url += '&search=' + encodeURIComponent(search);
    
    window.location.href = url;
}

function approveRequest(requestId, donationId) {
    if (!confirm('Êtes-vous sûr de vouloir approuver cette demande ? Un email avec les coordonnées du donneur sera envoyé au demandeur.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('donation_id', donationId);
    formData.append('status', 'approved');
    
    fetch(baseUrl + '/index.php?action=admin_donation_request_status', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid response format');
                }
            });
        }
    })
    .then(data => {
        if (data && data.success) {
            alert('Demande approuvée avec succès ! Un email a été envoyé au demandeur.');
            location.reload();
        } else {
            alert('Erreur: ' + (data?.error || 'Erreur lors de l\'approbation'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion. Veuillez réessayer.');
    });
}

function rejectRequest(requestId, donationId) {
    const reason = prompt('Raison du refus (optionnel):');
    if (reason === null) {
        return; // User cancelled
    }
    
    const formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('donation_id', donationId);
    formData.append('status', 'denied');
    formData.append('reason', reason || '');
    
    fetch(baseUrl + '/index.php?action=admin_donation_request_status', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid response format');
                }
            });
        }
    })
    .then(data => {
        if (data && data.success) {
            alert('Demande refusée avec succès !');
            location.reload();
        } else {
            alert('Erreur: ' + (data?.error || 'Erreur lors du refus'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion. Veuillez réessayer.');
    });
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
}
</script>


