<?php
/**
 * Admin Reclamations View
 * List all reclamations with filters and actions
 */
?>
<div class="page-heading">
    <h3>Gestion des Réclamations</h3>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/index.php?action=dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Réclamations</li>
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
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body">
                <h3><?= $stats['total'] ?></h3>
                <p class="mb-0"><i class="bi bi-inbox"></i> Total Réclamations</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <div class="card-body">
                <h3><?= $stats['en_attente'] ?></h3>
                <p class="mb-0"><i class="bi bi-clock"></i> En Attente</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <div class="card-body">
                <h3><?= $stats['en_cours'] ?></h3>
                <p class="mb-0"><i class="bi bi-arrow-repeat"></i> En Cours</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
            <div class="card-body">
                <h3><?= $stats['repondues'] ?></h3>
                <p class="mb-0"><i class="bi bi-check-circle"></i> Répondues</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <div class="card-body">
        <form method="GET" action="<?= $baseUrl ?>/index.php?action=dashboard&section=reclamations" class="row g-3">
            <input type="hidden" name="action" value="dashboard">
            <input type="hidden" name="section" value="reclamations">
            
            <div class="col-md-3">
                <label class="form-label">Priorité</label>
                <select name="priorite" id="filter-priorite" class="form-select" onchange="applyFilters()">
                    <option value="">Toutes</option>
                    <option value="Haute" <?= ($filters['priorite'] ?? '') === 'Haute' ? 'selected' : '' ?>>Haute</option>
                    <option value="Moyenne" <?= ($filters['priorite'] ?? '') === 'Moyenne' ? 'selected' : '' ?>>Moyenne</option>
                    <option value="Basse" <?= ($filters['priorite'] ?? '') === 'Basse' ? 'selected' : '' ?>>Basse</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select name="statut" id="filter-statut" class="form-select" onchange="applyFilters()">
                    <option value="">Tous</option>
                    <option value="En attente" <?= ($filters['statut'] ?? '') === 'En attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="En cours" <?= ($filters['statut'] ?? '') === 'En cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="Répondu" <?= ($filters['statut'] ?? '') === 'Répondu' ? 'selected' : '' ?>>Répondu</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="type" id="filter-type" class="form-select" onchange="applyFilters()">
                    <option value="">Tous</option>
                    <option value="Service" <?= ($filters['type'] ?? '') === 'Service' ? 'selected' : '' ?>>Service client</option>
                    <option value="Produit" <?= ($filters['type'] ?? '') === 'Produit' ? 'selected' : '' ?>>Qualité du produit</option>
                    <option value="Livraison" <?= ($filters['type'] ?? '') === 'Livraison' ? 'selected' : '' ?>>Livraison</option>
                    <option value="Facturation" <?= ($filters['type'] ?? '') === 'Facturation' ? 'selected' : '' ?>>Facturation</option>
                    <option value="Technique" <?= ($filters['type'] ?? '') === 'Technique' ? 'selected' : '' ?>>Technique</option>
                    <option value="Autre" <?= ($filters['type'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Rechercher</label>
                <div class="input-group">
                    <input type="text" name="search" id="search-input" class="form-control" 
                           placeholder="Nom, email..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                           onkeyup="debounceSearch()">
                    <button type="button" onclick="applyFilters()" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="<?= $baseUrl ?>/index.php?action=dashboard&section=reclamations" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reclamations Table -->
<div class="card">
    <div class="card-header">
        <h4>Liste des Réclamations</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Type</th>
                        <th>Priorité</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="reclamations-tbody">
                    <?php if (empty($reclamations)): ?>
                    <tr>
                        <td colspan="10" class="text-center">
                            <p class="text-muted">Aucune réclamation trouvée</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($reclamations as $rec): ?>
                    <tr>
                        <td>#<?= $rec['id'] ?></td>
                        <td>
                            <?php if ($rec['user_firstname'] || $rec['user_lastname']): ?>
                                <?= htmlspecialchars(trim(($rec['user_firstname'] ?? '') . ' ' . ($rec['user_lastname'] ?? ''))) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($rec['nom']) ?></td>
                        <td><?= htmlspecialchars($rec['email']) ?></td>
                        <td><?= htmlspecialchars($rec['telephone']) ?></td>
                        <td><?= htmlspecialchars($rec['type']) ?></td>
                        <td>
                            <span class="badge bg-<?= $rec['priorite'] === 'Haute' ? 'danger' : ($rec['priorite'] === 'Moyenne' ? 'warning' : 'success') ?>">
                                <?= htmlspecialchars($rec['priorite']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $rec['statut'] === 'En attente' ? 'secondary' : ($rec['statut'] === 'En cours' ? 'info' : 'success') ?>">
                                <?= htmlspecialchars($rec['statut']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($rec['date_creation'])) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button onclick="viewDetails(<?= $rec['id'] ?>)" class="btn btn-info" title="Voir détails">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button onclick="updateStatus(<?= $rec['id'] ?>, '<?= $rec['statut'] ?>')" 
                                        class="btn btn-warning" title="Changer statut">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button onclick="addResponse(<?= $rec['id'] ?>)" class="btn btn-success" title="Répondre">
                                    <i class="bi bi-reply"></i>
                                </button>
                                <button onclick="deleteReclamation(<?= $rec['id'] ?>)" class="btn btn-danger" title="Supprimer">
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

<!-- Modal: View Details -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la Réclamation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
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
                <input type="hidden" id="statusReclamationId">
                <div class="mb-3">
                    <label class="form-label">Nouveau Statut</label>
                    <select id="newStatus" class="form-select">
                        <option value="En attente">En attente</option>
                        <option value="En cours">En cours</option>
                        <option value="Répondu">Répondu</option>
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

<!-- Modal: Add Response -->
<div class="modal fade" id="responseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Répondre à la Réclamation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="responseReclamationId">
                <div class="mb-3">
                    <label class="form-label">Votre Réponse</label>
                    <textarea id="responseMessage" class="form-control" rows="5" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmAddResponse()">Envoyer</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetails(id) {
    fetch('<?= $baseUrl ?>/index.php?action=admin_reclamation_details&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const rec = data.reclamation;
                const responses = data.responses || [];
                
                let html = `
                    <div class="mb-3">
                        <strong>Nom:</strong> ${escapeHtml(rec.nom)}<br>
                        <strong>Email:</strong> ${escapeHtml(rec.email)}<br>
                        <strong>Téléphone:</strong> ${escapeHtml(rec.telephone)}<br>
                        <strong>Type:</strong> ${escapeHtml(rec.type)}<br>
                        <strong>Priorité:</strong> <span class="badge bg-${rec.priorite === 'Haute' ? 'danger' : (rec.priorite === 'Moyenne' ? 'warning' : 'success')}">${escapeHtml(rec.priorite)}</span><br>
                        <strong>Statut:</strong> <span class="badge bg-${rec.statut === 'En attente' ? 'secondary' : (rec.statut === 'En cours' ? 'info' : 'success')}">${escapeHtml(rec.statut)}</span><br>
                        <strong>Date:</strong> ${new Date(rec.date_creation).toLocaleString('fr-FR')}
                    </div>
                    <div class="mb-3">
                        <strong>Description:</strong>
                        <div class="p-3 bg-light rounded mt-2">${escapeHtml(rec.description).replace(/\n/g, '<br>')}</div>
                    </div>
                `;
                
                if (responses.length > 0) {
                    html += '<div class="mt-3"><strong>Réponses:</strong>';
                    responses.forEach(resp => {
                        html += `
                            <div class="p-3 bg-info bg-opacity-10 rounded mt-2">
                                <p>${escapeHtml(resp.message).replace(/\n/g, '<br>')}</p>
                                <small class="text-muted">
                                    ${resp.admin_firstname || resp.admin_lastname ? 'Par ' + escapeHtml(trim((resp.admin_firstname || '') + ' ' + (resp.admin_lastname || ''))) + ' - ' : ''}
                                    ${new Date(resp.date_reponse).toLocaleString('fr-FR')}
                                </small>
                            </div>
                        `;
                    });
                    html += '</div>';
                }
                
                document.getElementById('detailsContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('detailsModal')).show();
            } else {
                alert('Erreur: ' + (data.error || 'Impossible de charger les détails'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur de connexion');
        });
}

function updateStatus(id, currentStatus) {
    document.getElementById('statusReclamationId').value = id;
    document.getElementById('newStatus').value = currentStatus;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function confirmUpdateStatus() {
    const id = document.getElementById('statusReclamationId').value;
    const status = document.getElementById('newStatus').value;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', status);
    
    fetch('<?= $baseUrl ?>/index.php?action=admin_reclamation_update_status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Statut mis à jour avec succès');
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de mettre à jour'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion');
    });
}

function addResponse(id) {
    document.getElementById('responseReclamationId').value = id;
    document.getElementById('responseMessage').value = '';
    new bootstrap.Modal(document.getElementById('responseModal')).show();
}

function confirmAddResponse() {
    const id = document.getElementById('responseReclamationId').value;
    const message = document.getElementById('responseMessage').value.trim();
    
    if (!message) {
        alert('Veuillez saisir une réponse');
        return;
    }
    
    const formData = new FormData();
    formData.append('reclamation_id', id);
    formData.append('message', message);
    
    fetch('<?= $baseUrl ?>/index.php?action=admin_reclamation_add_response', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Réponse ajoutée avec succès');
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible d\'ajouter la réponse'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion');
    });
}

function deleteReclamation(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ? Cette action est irréversible.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('<?= $baseUrl ?>/index.php?action=admin_reclamation_delete&id=' + id, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Réclamation supprimée avec succès');
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de supprimer'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur de connexion');
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function trim(str) {
    return str.replace(/^\s+|\s+$/g, '');
}

// Filter and search functionality
let searchTimeout;
const baseUrl = '<?= $baseUrl ?>';
let isLoading = false;

function applyFilters() {
    if (isLoading) return;
    
    const priorite = document.getElementById('filter-priorite').value;
    const statut = document.getElementById('filter-statut').value;
    const type = document.getElementById('filter-type').value;
    const search = document.getElementById('search-input').value.trim();
    
    // Show loading state
    const tbody = document.getElementById('reclamations-tbody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></td></tr>';
    }
    
    isLoading = true;
    
    // Build URL with filters
    let url = baseUrl + '/index.php?action=api_admin_reclamations';
    if (priorite) url += '&priorite=' + encodeURIComponent(priorite);
    if (statut) url += '&statut=' + encodeURIComponent(statut);
    if (type) url += '&type=' + encodeURIComponent(type);
    if (search) url += '&search=' + encodeURIComponent(search);
    
    // Update URL without reloading (for browser history)
    const newUrl = baseUrl + '/index.php?action=dashboard&section=reclamations' + 
        (priorite ? '&priorite=' + encodeURIComponent(priorite) : '') +
        (statut ? '&statut=' + encodeURIComponent(statut) : '') +
        (type ? '&type=' + encodeURIComponent(type) : '') +
        (search ? '&search=' + encodeURIComponent(search) : '');
    window.history.pushState({}, '', newUrl);
    
    // Fetch filtered reclamations via AJAX
    fetch(url)
        .then(response => response.json())
        .then(data => {
            isLoading = false;
            if (data.success) {
                renderReclamations(data.reclamations);
            } else {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center"><p class="text-muted">Erreur lors du chargement</p></td></tr>';
            }
        })
        .catch(error => {
            isLoading = false;
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="10" class="text-center"><p class="text-muted">Erreur de connexion</p></td></tr>';
        });
}

function renderReclamations(reclamations) {
    const tbody = document.getElementById('reclamations-tbody');
    if (!tbody) return;
    
    if (reclamations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center"><p class="text-muted">Aucune réclamation trouvée</p></td></tr>';
        return;
    }
    
    let html = '';
    reclamations.forEach(rec => {
        const priorityClass = rec.priorite === 'Haute' ? 'danger' : (rec.priorite === 'Moyenne' ? 'warning' : 'success');
        const statusClass = rec.statut === 'En attente' ? 'secondary' : (rec.statut === 'En cours' ? 'info' : 'success');
        const userName = (rec.user_firstname || rec.user_lastname) 
            ? escapeHtml(trim((rec.user_firstname || '') + ' ' + (rec.user_lastname || '')))
            : '<span class="text-muted">-</span>';
        
        html += `
            <tr>
                <td>#${rec.id}</td>
                <td>${userName}</td>
                <td>${escapeHtml(rec.nom)}</td>
                <td>${escapeHtml(rec.email)}</td>
                <td>${escapeHtml(rec.telephone)}</td>
                <td>${escapeHtml(rec.type)}</td>
                <td>
                    <span class="badge bg-${priorityClass}">${escapeHtml(rec.priorite)}</span>
                </td>
                <td>
                    <span class="badge bg-${statusClass}">${escapeHtml(rec.statut)}</span>
                </td>
                <td>${new Date(rec.date_creation).toLocaleString('fr-FR')}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button onclick="viewDetails(${rec.id})" class="btn btn-info" title="Voir détails">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button onclick="updateStatus(${rec.id}, '${escapeHtml(rec.statut)}')" 
                                class="btn btn-warning" title="Changer statut">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button onclick="addResponse(${rec.id})" class="btn btn-success" title="Répondre">
                            <i class="bi bi-reply"></i>
                        </button>
                        <button onclick="deleteReclamation(${rec.id})" class="btn btn-danger" title="Supprimer">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        applyFilters();
    }, 500); // Wait 500ms after user stops typing
}
</script>

