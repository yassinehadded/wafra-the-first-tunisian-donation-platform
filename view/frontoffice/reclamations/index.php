<?php
/**
 * Reclamations View - Front Office
 * Form to submit reclamation and list user's reclamations
 */
$pageTitle = 'Réclamations - Wafra';
$pageDescription = 'Soumettez une réclamation ou consultez vos réclamations';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap core CSS -->
    <link href="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/fontawesome.css">
    
    <!-- Top Bar CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar-enhanced.css">
    
    <!-- Chatbot CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/components/chatbot.css">
    
    <style>
        :root {
            --primary-color: #f5a425;
            --primary-hover: #e0941a;
            --text-primary: #1a1a1a;
            --text-secondary: #666;
            --text-light: #999;
            --bg-white: #ffffff;
            --bg-light: #f8f9fa;
            --border-color: #e0e0e0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --radius: 12px;
            --radius-sm: 8px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            padding-top: 80px;
        }
        
        .reclamations-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .reclamation-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .reclamation-form {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            display: block;
        }
        
        .form-group label.required::after {
            content: " *";
            color: #dc3545;
        }
        
        .form-control {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(245, 164, 37, 0.1);
            outline: none;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 12px 30px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 164, 37, 0.3);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-en-attente { background: #6c757d; color: white; }
        .status-en-cours { background: #17a2b8; color: white; }
        .status-repondu { background: #28a745; color: white; }
        
        .priority-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .priority-haute { background: #dc3545; color: white; }
        .priority-moyenne { background: #ffc107; color: #000; }
        .priority-basse { background: #28a745; color: white; }
        
        .alert {
            border-radius: var(--radius-sm);
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .reclamation-item {
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
        }
        
        .reclamation-item:last-child {
            border-bottom: none;
        }
        
        .reclamation-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-sm {
            padding: 6px 15px;
            font-size: 13px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state-icon {
            font-size: 64px;
            color: var(--text-light);
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="no-sidebar">

<!-- Top Bar -->
<div class="top-bar">
    <div class="top-bar-left">
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" style="text-decoration: none;">
            <h4 style="margin: 0; color: #ffd700; font-weight: 700;">Wafra</h4>
        </a>
    </div>
    <div class="top-bar-right">
        <div class="user-info">
            <i class="fa fa-user-circle" style="font-size: 24px; color: #f5a425;"></i>
            <span class="user-name"><?= htmlspecialchars($_SESSION['firstname'] ?? '') . ' ' . htmlspecialchars($_SESSION['lastname'] ?? '') ?></span>
        </div>
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" class="profile-link" style="margin-right: 10px;">
            <i class="fa fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/posts.php" class="profile-link">
            <i class="fa fa-comments"></i>
            <span>Posts</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/messages.php" class="profile-link" style="position: relative;">
            <i class="fa fa-envelope"></i>
            <span>Messages</span>
        </a>
        <a href="<?= $baseUrl ?>/index.php?action=reclamations" class="profile-link" style="background: rgba(245, 164, 37, 0.2);">
            <i class="fa fa-exclamation-circle"></i>
            <span>Réclamations</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/profile.php" class="profile-link">
            <i class="fa fa-user"></i>
            <span>Profil</span>
        </a>
        <a href="<?= $baseUrl ?>/index.php?action=logout" class="logout-link">
            <i class="fa fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="reclamations-container">
        <div class="row">
            <div class="col-md-12">
                <h1 style="margin-bottom: 30px; color: var(--text-primary);">
                    <i class="fa fa-exclamation-circle"></i> Mes Réclamations
                </h1>
            </div>
        </div>
        
        <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> <?= $errorMessage ?>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Form Section -->
            <div class="col-md-6">
                <div class="reclamation-form">
                    <h3 style="margin-bottom: 25px; color: var(--text-primary);">
                        <i class="fa fa-plus-circle"></i> Nouvelle Réclamation
                    </h3>
                    
                    <form method="POST" action="<?= $baseUrl ?>/index.php?action=reclamation_submit" id="reclamationForm">
                        <div class="form-group">
                            <label class="required">Nom complet</label>
                            <input type="text" name="nom" class="form-control" 
                                   value="<?= htmlspecialchars($currentUser['firstname'] ?? '') ?> <?= htmlspecialchars($currentUser['lastname'] ?? '') ?>"
                                   required pattern="[A-Za-zÀ-ÿ\s]{3,50}" 
                                   title="3 à 50 caractères, lettres uniquement">
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control" 
                                   placeholder="8 chiffres" maxlength="8" 
                                   pattern="[0-9]{8}" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Type de réclamation</label>
                            <select name="type" class="form-control" required>
                                <option value="">-- Sélectionnez --</option>
                                <option value="Service">Service client</option>
                                <option value="Produit">Qualité du produit</option>
                                <option value="Livraison">Problème de livraison</option>
                                <option value="Facturation">Erreur de facturation</option>
                                <option value="Technique">Problème technique</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Priorité</label>
                            <select name="priorite" class="form-control" required>
                                <option value="">-- Sélectionnez --</option>
                                <option value="Basse">🟢 Basse - Peut attendre</option>
                                <option value="Moyenne">🟡 Moyenne - Important</option>
                                <option value="Haute">🔴 Haute - Urgent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Description détaillée</label>
                            <textarea name="description" class="form-control" rows="6" 
                                      placeholder="Décrivez votre réclamation en détail (minimum 20 caractères)..." 
                                      required minlength="20"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-paper-plane"></i> Envoyer la réclamation
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- List Section -->
            <div class="col-md-6">
                <div class="reclamation-card">
                    <h3 style="margin-bottom: 25px; color: var(--text-primary);">
                        <i class="fa fa-list"></i> Historique
                    </h3>
                    
                    <?php if (empty($reclamations)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fa fa-inbox"></i>
                        </div>
                        <h4>Aucune réclamation</h4>
                        <p>Vous n'avez pas encore soumis de réclamation.</p>
                    </div>
                    <?php else: ?>
                    <div id="reclamationsList">
                        <?php foreach ($reclamations as $rec): ?>
                        <div class="reclamation-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong>#<?= $rec['id'] ?></strong> - <?= htmlspecialchars($rec['type']) ?>
                                </div>
                                <div>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $rec['statut'])) ?>">
                                        <?= htmlspecialchars($rec['statut']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <span class="priority-badge priority-<?= strtolower($rec['priorite']) ?>">
                                    <?= htmlspecialchars($rec['priorite']) ?>
                                </span>
                                <small class="text-muted ml-2">
                                    <i class="fa fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($rec['date_creation'])) ?>
                                </small>
                            </div>
                            
                            <p class="text-muted" style="font-size: 14px; margin-bottom: 10px;">
                                <?= htmlspecialchars(mb_substr($rec['description'], 0, 100)) ?><?= mb_strlen($rec['description']) > 100 ? '...' : '' ?>
                            </p>
                            
                            <?php if ($rec['nb_reponses'] > 0): ?>
                            <div class="alert alert-info" style="padding: 10px; margin-bottom: 10px; font-size: 13px;">
                                <i class="fa fa-reply"></i> <?= $rec['nb_reponses'] ?> réponse(s) disponible(s)
                            </div>
                            <?php endif; ?>
                            
                            <div class="reclamation-actions">
                                <a href="<?= $baseUrl ?>/index.php?action=reclamation_view&id=<?= $rec['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fa fa-eye"></i> Voir
                                </a>
                                <?php if ($rec['statut'] === 'En attente'): ?>
                                <button onclick="deleteReclamation(<?= $rec['id'] ?>)" 
                                        class="btn btn-sm btn-danger">
                                    <i class="fa fa-trash"></i> Supprimer
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    $chatbotPath = __DIR__ . '/../../components/chatbot.php';
    if (file_exists($chatbotPath)) {
        require_once $chatbotPath;
    }
    ?>
    
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteReclamation(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?')) {
                return;
            }
            
            fetch('<?= $baseUrl ?>/index.php?action=reclamation_delete&id=' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Réclamation supprimée avec succès');
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.error || 'Erreur lors de la suppression'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur de connexion');
            });
        }
    </script>
</body>
</html>

