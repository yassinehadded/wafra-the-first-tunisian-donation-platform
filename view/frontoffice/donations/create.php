<?php
/**
 * Create Donation View
 * Form to create a new donation
 */
$pageTitle = 'Créer une Donation - Wafra';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= $baseUrl ?>/view/frontoffice/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/fontawesome.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/frontoffice/assets/css/topbar-enhanced.css">
    
    <style>
        :root {
            --primary-color: #f5a425;
            --text-primary: #1a1a1a;
            --text-secondary: #666;
            --bg-white: #ffffff;
            --bg-light: #f8f9fa;
            --border-color: #e0e0e0;
            --radius: 12px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            padding-top: 80px;
        }
        
        .create-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .create-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(245, 164, 37, 0.1);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            color: white;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background: #e0941a;
        }
    </style>
</head>
<body>

<!-- Top Bar (same as feed.php) -->
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
        <!-- Notifications Bell -->
        <div class="notification-container" style="position: relative; margin-right: 10px;">
            <button class="notification-bell" id="notificationBell" onclick="toggleNotificationDropdown()" aria-label="Notifications" style="background: transparent; border: none; color: #fff; font-size: 20px; cursor: pointer; padding: 8px 12px; position: relative;">
                <i class="fa fa-bell"></i>
                <span class="notification-badge" id="notificationBadge" style="position: absolute; top: 5px; right: 5px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: none; align-items: center; justify-content: center; font-weight: bold; z-index: 1000; line-height: 18px; text-align: center;">0</span>
            </button>
            <div class="notification-dropdown" id="notificationDropdown" style="position: absolute; top: 100%; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 350px; max-height: 500px; overflow-y: auto; z-index: 1000; margin-top: 10px; display: none;">
                <div class="notification-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa;">
                    <h5 style="margin: 0; font-weight: 600;">Notifications</h5>
                    <button onclick="markAllNotificationsRead()" style="background: none; border: none; color: #f5a425; cursor: pointer; font-size: 12px;">Tout marquer comme lu</button>
                </div>
                <div class="notification-list" id="notificationList" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center" style="padding: 20px; color: #999;">
                        <i class="fa fa-spinner fa-spin"></i> Chargement...
                    </div>
                </div>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/view/frontoffice/index.php" class="profile-link" style="margin-right: 10px;">
            <i class="fa fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/posts.php" class="profile-link" style="margin-right: 10px;">
            <i class="fa fa-comments"></i>
            <span>Posts</span>
        </a>
        <a href="<?= $baseUrl ?>/index.php?action=donations" class="profile-link" style="background: rgba(245, 164, 37, 0.2); margin-right: 10px;">
            <i class="fa fa-gift"></i>
            <span>Donations</span>
        </a>
        <a href="<?= $baseUrl ?>/view/frontoffice/messages.php" class="profile-link" style="position: relative; margin-right: 10px;">
            <i class="fa fa-envelope"></i>
            <span>Messages</span>
            <span class="message-badge" id="messageBadge" style="position: absolute; top: -5px; right: -5px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: none; align-items: center; justify-content: center; font-weight: bold; z-index: 1000; line-height: 18px; text-align: center;">0</span>
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

<div class="create-container">
    <div class="create-card">
        <h1 style="margin-bottom: 30px;"><i class="fa fa-plus-circle"></i> Créer une Donation</h1>
        
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
        
        <form method="POST" action="<?= $baseUrl ?>/index.php?action=donation_submit" enctype="multipart/form-data">
            <div class="form-group">
                <label class="required">Nom du donateur</label>
                <input type="text" name="donor_name" class="form-control" 
                       value="<?= htmlspecialchars($oldData['donor_name'] ?? ($currentUser['firstname'] ?? '') . ' ' . ($currentUser['lastname'] ?? '')) ?>"
                       required pattern="[A-Za-zÀ-ÿ\s]{3,50}">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="donor_email" class="form-control" 
                       value="<?= htmlspecialchars($oldData['donor_email'] ?? ($currentUser['email'] ?? '')) ?>">
            </div>
            
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" name="donor_phone" class="form-control" 
                       placeholder="8 chiffres" maxlength="8" pattern="[0-9]{8}"
                       value="<?= htmlspecialchars($oldData['donor_phone'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="required">Titre</label>
                <input type="text" name="title" class="form-control" 
                       placeholder="Ex: Livres d'occasion" required minlength="3"
                       value="<?= htmlspecialchars($oldData['title'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4" 
                          placeholder="Décrivez votre donation..."><?= htmlspecialchars($oldData['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="required">Catégorie</label>
                <select name="category" class="form-control" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="Books" <?= ($oldData['category'] ?? '') === 'Books' ? 'selected' : '' ?>>Livres</option>
                    <option value="Clothing" <?= ($oldData['category'] ?? '') === 'Clothing' ? 'selected' : '' ?>>Vêtements</option>
                    <option value="Electronics" <?= ($oldData['category'] ?? '') === 'Electronics' ? 'selected' : '' ?>>Électronique</option>
                    <option value="Food" <?= ($oldData['category'] ?? '') === 'Food' ? 'selected' : '' ?>>Nourriture</option>
                    <option value="Other" <?= ($oldData['category'] ?? '') === 'Other' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="required">Quantité</label>
                <input type="number" name="quantity" class="form-control" 
                       min="1" required value="<?= htmlspecialchars($oldData['quantity'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Image</label>
                <input type="file" name="item_image" class="form-control" 
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <small style="color: var(--text-secondary);">Formats acceptés: JPEG, PNG, GIF, WebP (max 5MB)</small>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-paper-plane"></i> Publier la donation
                </button>
                <a href="<?= $baseUrl ?>/index.php?action=donations" class="btn" style="background: #6c757d; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none;">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script src="<?= htmlspecialchars($baseUrl) ?>/view/frontoffice/vendor/jquery/jquery.min.js"></script>
<script src="<?= htmlspecialchars($baseUrl) ?>/view/frontoffice/vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>

