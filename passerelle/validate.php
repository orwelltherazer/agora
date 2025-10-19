<?php

/**
 * Page publique de validation pour la passerelle
 * Les validateurs accèdent à cette page via le lien dans l'email
 * URL: https://passerelle.com/validate/{token}
 */

require_once __DIR__ . '/database.php';

$config = require __DIR__ . '/config.php';

// Récupérer le token depuis l'URL
$requestUri = $_SERVER['REQUEST_URI'];
preg_match('/\/validate\/([a-zA-Z0-9]+)/', $requestUri, $matches);
$token = $matches[1] ?? $_GET['token'] ?? '';

if (empty($token)) {
    die('Erreur: Token de validation manquant');
}

$pdo = getPasserelleDatabase();

// Vérifier si le token a déjà été utilisé
$stmt = $pdo->prepare("SELECT * FROM validation_responses WHERE token = ?");
$stmt->execute([$token]);
$existingValidation = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement de la soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $commentaire = $_POST['commentaire'] ?? '';

    if (!in_array($action, ['valide', 'refuse'])) {
        $error = 'Action invalide';
    } elseif ($existingValidation) {
        $error = 'Ce lien de validation a déjà été utilisé';
    } else {
        // Récupérer les informations du token depuis Agora via API
        // Pour l'instant, on va stocker temporairement sans validation
        // En production, il faudrait vérifier le token contre l'API Agora

        try {
            // Stocker la validation dans la queue
            $stmt = $pdo->prepare("
                INSERT INTO validation_responses (token, campaign_id, user_id, action, commentaire, validated_at)
                VALUES (?, ?, ?, ?, ?, datetime('now'))
            ");

            // Note: campaign_id et user_id seront récupérés depuis le token via l'API Agora
            // Pour l'instant on met des valeurs temporaires qui seront écrasées par la sync
            $stmt->execute([
                $token,
                0, // Sera mis à jour lors de la synchronisation
                0, // Sera mis à jour lors de la synchronisation
                $action,
                $commentaire
            ]);

            $success = true;
            $successMessage = $action === 'valide'
                ? 'Merci ! Votre validation a bien été enregistrée.'
                : 'Votre refus a bien été enregistré.';

        } catch (PDOException $e) {
            $error = 'Erreur lors de l\'enregistrement de votre validation';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation de campagne - Agora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-2xl w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-600 rounded-full mb-4">
                    <i class="fas fa-comments text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Agora</h1>
                <p class="text-gray-600 mt-2">Gestion des communications</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-8">
                <?php if (isset($success) && $success): ?>
                    <!-- Message de succès -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                            <i class="fas fa-check-circle text-green-600 text-3xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Validation enregistrée</h2>
                        <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($successMessage); ?></p>
                        <p class="text-sm text-gray-500">
                            Votre réponse sera synchronisée prochainement avec le système interne.
                        </p>
                    </div>

                <?php elseif ($existingValidation): ?>
                    <!-- Token déjà utilisé -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4">
                            <i class="fas fa-exclamation-triangle text-orange-600 text-3xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Lien déjà utilisé</h2>
                        <p class="text-gray-600">
                            Ce lien de validation a déjà été utilisé le <?php echo date('d/m/Y à H:i', strtotime($existingValidation['validated_at'])); ?>.
                        </p>
                        <p class="text-sm text-gray-500 mt-4">
                            Action enregistrée : <strong><?php echo $existingValidation['action'] === 'valide' ? 'Validé' : 'Refusé'; ?></strong>
                        </p>
                    </div>

                <?php else: ?>
                    <!-- Formulaire de validation -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Validation de campagne</h2>
                        <p class="text-gray-600 mb-6">
                            Merci de donner votre avis sur cette campagne de communication.
                        </p>

                        <?php if (isset($error)): ?>
                            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-2"></i>
                                    <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <!-- Commentaire -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Commentaire (optionnel)
                                </label>
                                <textarea name="commentaire" rows="4"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Vos remarques, suggestions..."></textarea>
                            </div>

                            <!-- Boutons d'action -->
                            <div class="flex gap-4">
                                <button type="submit" name="action" value="valide"
                                        class="flex-1 px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                                    <i class="fas fa-check-circle"></i>
                                    Valider
                                </button>
                                <button type="submit" name="action" value="refuse"
                                        class="flex-1 px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center gap-2">
                                    <i class="fas fa-times-circle"></i>
                                    Refuser
                                </button>
                            </div>
                        </form>

                        <p class="text-xs text-gray-500 mt-6 text-center">
                            <i class="fas fa-lock mr-1"></i>
                            Ce lien est unique et ne peut être utilisé qu'une seule fois.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-sm text-gray-500">
                <p>© <?php echo date('Y'); ?> Agora - Gestion des communications</p>
            </div>
        </div>
    </div>
</body>
</html>
