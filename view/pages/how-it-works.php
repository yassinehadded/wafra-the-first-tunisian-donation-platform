<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Comment ça marche';
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
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><i class="bi bi-question-circle"></i> Comment ça marche</h2>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="bi bi-person-plus" style="font-size: 3rem; color: #667eea;"></i>
                            <h5 class="mt-2">1. Inscrivez-vous</h5>
                            <p>Créez votre compte gratuitement et rejoignez la communauté WAFRA.</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="bi bi-heart" style="font-size: 3rem; color: #e74c3c;"></i>
                            <h5 class="mt-2">2. Explorez</h5>
                            <p>Découvrez les événements, dons, associations et opportunités disponibles.</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center">
                            <i class="bi bi-hand-thumbs-up" style="font-size: 3rem; color: #28a745;"></i>
                            <h5 class="mt-2">3. Contribuez</h5>
                            <p>Participez, donnez, ou créez votre propre initiative pour aider les autres.</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=about" class="btn btn-outline-primary">À propos</a>
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=mission" class="btn btn-outline-primary">Notre Mission</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



