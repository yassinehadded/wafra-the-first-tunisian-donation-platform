<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Notre Mission';
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
                <h2 class="mb-0"><i class="bi bi-bullseye"></i> Notre Mission</h2>
            </div>
            <div class="card-body p-4">
                <h4>Objectif Principal</h4>
                <p>WAFRA vise à créer un écosystème de solidarité où chaque membre de la communauté peut contribuer et bénéficier de l'entraide mutuelle.</p>
                
                <h4 class="mt-4">Nos Valeurs</h4>
                <ul>
                    <li><strong>Solidarité</strong> : Nous croyons en la force de la communauté</li>
                    <li><strong>Transparence</strong> : Toutes nos actions sont transparentes et vérifiables</li>
                    <li><strong>Respect</strong> : Nous respectons la dignité de chaque personne</li>
                    <li><strong>Engagement</strong> : Nous nous engageons à faire une différence positive</li>
                </ul>
                
                <div class="mt-4">
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=about" class="btn btn-outline-primary">À propos</a>
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=how-it-works" class="btn btn-outline-primary">Comment ça marche</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



