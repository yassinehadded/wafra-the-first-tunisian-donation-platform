<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Centre d\'aide / FAQ';
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
                <h2 class="mb-0"><i class="bi bi-question-circle"></i> Centre d'aide / FAQ</h2>
            </div>
            <div class="card-body p-4">
                <h4>Questions fréquentes</h4>
                <div class="accordion mt-3" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Comment créer un compte ?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Cliquez sur "Inscription" et remplissez le formulaire avec vos informations. Vous recevrez un email de confirmation.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Comment faire un don ?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Accédez à la section "Donations", parcourez les dons disponibles et suivez les instructions pour faire votre don.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Comment rejoindre une association ?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Visitez la section "Associations", choisissez une association et cliquez sur "Rejoindre".
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=contact" class="btn btn-outline-primary">Nous contacter</a>
                    <a href="<?= $baseUrl ?>/index.php?action=page&name=report" class="btn btn-outline-danger">Signaler un problème</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



