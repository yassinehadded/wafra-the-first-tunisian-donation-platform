<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Politique de confidentialité';
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
                <h2 class="mb-0"><i class="bi bi-shield-lock"></i> Politique de confidentialité</h2>
            </div>
            <div class="card-body p-4">
                <p><strong>Dernière mise à jour :</strong> <?= date('d/m/Y') ?></p>
                <h4 class="mt-4">Collecte des données</h4>
                <p>WAFRA collecte uniquement les données nécessaires au fonctionnement de la plateforme et à l'amélioration de nos services.</p>
                <h4 class="mt-4">Utilisation des données</h4>
                <p>Vos données personnelles sont utilisées pour :</p>
                <ul>
                    <li>Gérer votre compte et vos interactions</li>
                    <li>Faciliter les transactions et communications</li>
                    <li>Améliorer nos services</li>
                    <li>Respecter nos obligations légales</li>
                </ul>
                <h4 class="mt-4">Protection des données</h4>
                <p>Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos données personnelles contre tout accès non autorisé, altération, divulgation ou destruction.</p>
                <h4 class="mt-4">Vos droits</h4>
                <p>Conformément au RGPD, vous disposez du droit d'accès, de rectification, de suppression et de portabilité de vos données personnelles.</p>
                <div class="mt-4">
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=transparency" class="btn btn-outline-primary">Transparence</a>
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=terms" class="btn btn-outline-primary">Conditions d'utilisation</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



