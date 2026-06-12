<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'À propos de WAFRA';
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
        body { padding-top: 2rem; background: #f8f9fa; min-height: 100vh; }
        .content-card { max-width: 900px; margin: 0 auto; }
        .back-link { margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="<?= $baseUrl ?>/view/frontoffice/index.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
        <div class="content-card card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><i class="bi bi-info-circle"></i> À propos de WAFRA</h2>
            </div>
            <div class="card-body p-4">
                <p class="lead">WAFRA est une plateforme communautaire dédiée à la solidarité et à l'entraide.</p>
                <p>Notre mission est de connecter les personnes qui souhaitent aider avec celles qui ont besoin d'assistance, en créant un réseau de soutien mutuel au sein de notre communauté.</p>
                <p>À travers notre plateforme, vous pouvez participer à des événements, faire des dons, rejoindre des associations, et contribuer à des causes qui vous tiennent à cœur.</p>
                <div class="mt-4">
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=mission" class="btn btn-outline-primary">Notre Mission</a>
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=how-it-works" class="btn btn-outline-primary">Comment ça marche</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

