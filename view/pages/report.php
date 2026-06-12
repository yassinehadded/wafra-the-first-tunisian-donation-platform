<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Signaler un problème';
$baseUrl = BASE_URL;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { padding-top: 2rem; background: #f8f9fa; }
        .content-card { max-width: 900px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="content-card card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h2 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Signaler un problème</h2>
            </div>
            <div class="card-body p-4">
                <p>Si vous rencontrez un problème technique, un bug, ou un comportement inattendu sur la plateforme, veuillez nous le signaler.</p>
                <form method="POST" action="<?= $baseUrl ?>/index.php?action=page&name=report&submit=1">
                    <div class="mb-3">
                        <label class="form-label">Votre email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sujet</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description du problème</label>
                        <textarea name="description" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">Envoyer le signalement</button>
                </form>
                <?php if (isset($_GET['submit']) && $_GET['submit'] == '1'): ?>
                    <div class="alert alert-success mt-3">
                        <i class="bi bi-check-circle"></i> Votre signalement a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.
                    </div>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=help" class="btn btn-outline-primary">Centre d'aide</a>
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=contact" class="btn btn-outline-primary">Nous contacter</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



