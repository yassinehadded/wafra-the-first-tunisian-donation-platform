<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Nous contacter';
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
                <h2 class="mb-0"><i class="bi bi-envelope"></i> Nous contacter</h2>
            </div>
            <div class="card-body p-4">
                <p>Pour toute question, suggestion ou demande d'assistance, n'hésitez pas à nous contacter :</p>
                <div class="mt-4">
                    <p><i class="bi bi-envelope"></i> <strong>Email :</strong> contact@wafra.com</p>
                    <p><i class="bi bi-telephone"></i> <strong>Téléphone :</strong> +33 1 23 45 67 89</p>
                    <p><i class="bi bi-clock"></i> <strong>Horaires :</strong> Lundi - Vendredi, 9h - 18h</p>
                </div>
                <div class="mt-4">
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=help" class="btn btn-outline-primary">Centre d'aide</a>
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=report" class="btn btn-outline-danger">Signaler un problème</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



