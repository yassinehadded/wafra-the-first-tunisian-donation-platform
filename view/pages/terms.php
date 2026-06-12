<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Conditions d\'utilisation';
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
                <h2 class="mb-0"><i class="bi bi-file-text"></i> Conditions d'utilisation</h2>
            </div>
            <div class="card-body p-4">
                <p><strong>Dernière mise à jour :</strong> <?= date('d/m/Y') ?></p>
                <h4 class="mt-4">Acceptation des conditions</h4>
                <p>En utilisant la plateforme WAFRA, vous acceptez d'être lié par ces conditions d'utilisation.</p>
                <h4 class="mt-4">Utilisation de la plateforme</h4>
                <p>Vous vous engagez à :</p>
                <ul>
                    <li>Utiliser la plateforme de manière légale et conforme</li>
                    <li>Respecter les droits des autres utilisateurs</li>
                    <li>Ne pas publier de contenu offensant ou illégal</li>
                    <li>Maintenir la confidentialité de votre compte</li>
                </ul>
                <h4 class="mt-4">Responsabilité</h4>
                <p>WAFRA ne peut être tenu responsable des dommages résultant de l'utilisation ou de l'impossibilité d'utiliser la plateforme.</p>
                <h4 class="mt-4">Modifications</h4>
                <p>Nous nous réservons le droit de modifier ces conditions à tout moment. Les modifications entreront en vigueur dès leur publication.</p>
                <div class="mt-4">
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=transparency" class="btn btn-outline-primary">Transparence</a>
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=privacy" class="btn btn-outline-primary">Politique de confidentialité</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



