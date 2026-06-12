<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Transparence';
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
                <h2 class="mb-0"><i class="bi bi-eye"></i> Transparence</h2>
            </div>
            <div class="card-body p-4">
                <p>WAFRA s'engage à maintenir une transparence totale dans toutes ses opérations.</p>
                <h4 class="mt-4">Nos engagements</h4>
                <ul>
                    <li>Tous les dons et contributions sont tracés et vérifiables</li>
                    <li>Les associations sont validées et certifiées</li>
                    <li>Les transactions sont sécurisées et transparentes</li>
                    <li>Les rapports d'activité sont publiés régulièrement</li>
                </ul>
                <div class="mt-4">
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=privacy" class="btn btn-outline-primary">Politique de confidentialité</a>
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=terms" class="btn btn-outline-primary">Conditions d'utilisation</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



