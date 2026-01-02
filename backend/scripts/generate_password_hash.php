<?php
/**
 * Générateur de hash sécurisé pour mots de passe
 * 
 * SÉCURITÉ: Utilise bcrypt avec salt automatique pour le hachage
 * Usage: php generate_password_hash.php "VotreMotDePasse"
 * 
 * @author Memory Project
 * @version 1.0
 */

// Vérification des arguments CLI pour éviter les erreurs d'exécution
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('❌ Ce script ne peut être exécuté qu\'en ligne de commande pour des raisons de sécurité.');
}

if ($argc !== 2) {
    echo "❌ Usage incorrect\n";
    echo "📖 Syntaxe: php generate_password_hash.php \"VotreMotDePasse\"\n";
    echo "📝 Exemple: php generate_password_hash.php \"MemorySecure2024!\"\n\n";
    echo "⚠️  IMPORTANT: Utilisez des guillemets pour protéger les caractères spéciaux\n";
    exit(1);
}

$password = $argv[1];

// Validation robuste du mot de passe selon les critères de sécurité modernes
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Longueur minimum: 8 caractères";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Au moins une majuscule requise";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Au moins une minuscule requise";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Au moins un chiffre requis";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Au moins un caractère spécial requis (!@#$%^&*)";
    }
    
    return $errors;
}

// Vérification de la robustesse du mot de passe
$validationErrors = validatePasswordStrength($password);

if (!empty($validationErrors)) {
    echo "⚠️  ATTENTION: Le mot de passe ne respecte pas les critères de sécurité:\n";
    foreach ($validationErrors as $error) {
        echo "   • $error\n";
    }
    echo "\n🔒 Recommandations:\n";
    echo "   • Minimum 8 caractères\n";
    echo "   • Mélange majuscules/minuscules\n";
    echo "   • Au moins un chiffre\n";
    echo "   • Au moins un caractère spécial\n";
    echo "\nContinuer malgré tout ? (y/N): ";
    
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($input) !== 'y') {
        echo "Opération annulée.\n";
        exit(0);
    }
}

try {
    // Génération du hash avec bcrypt (coût 12 pour sécurité renforcée)
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    if ($hash === false) {
        throw new Exception('Échec de la génération du hash');
    }
    
    // Vérification immédiate de l'intégrité du hash généré
    if (!password_verify($password, $hash)) {
        throw new Exception('Le hash généré ne correspond pas au mot de passe - erreur critique');
    }
    
    // Affichage sécurisé des résultats
    echo "\n✅ Hash généré avec succès!\n";
    echo str_repeat("━", 80) . "\n";
    echo "🔐 Algorithme: bcrypt (coût 12)\n";
    echo "📏 Longueur hash: " . strlen($hash) . " caractères\n";
    echo "🔑 Hash: $hash\n";
    echo str_repeat("━", 80) . "\n\n";
    
    // Préparation de la requête SQL sécurisée
    // Note: Échappement du hash pour éviter les problèmes d'injection
    $escapedHash = addslashes($hash);
    $sqlQuery = "UPDATE User SET Password_hash = '$escapedHash' WHERE Email_Unique = 'admin@memory.local';";
    
    echo "📋 Requête SQL à exécuter:\n";
    echo str_repeat("─", 50) . "\n";
    echo "$sqlQuery\n";
    echo str_repeat("─", 50) . "\n\n";
    
    // Instructions détaillées pour l'exécution
    echo "📝 Instructions d'exécution:\n";
    echo "1. 🌐 Ouvrez phpMyAdmin → http://localhost:8888/phpMyAdmin/\n";
    echo "2. 🗄️  Sélectionnez la base de données 'Memory'\n";
    echo "3. 📝 Cliquez sur l'onglet 'SQL'\n";
    echo "4. 📋 Copiez-collez la requête ci-dessus\n";
    echo "5. ▶️  Cliquez sur 'Exécuter'\n";
    echo "6. ✅ Vérifiez le message de confirmation\n\n";
    
    echo "🧪 Test de connexion après mise à jour:\n";
    echo "   🌐 URL: http://localhost:5173/login\n";
    echo "   📧 Email: admin@memory.local\n";
    echo "   🔑 Password: [le mot de passe que vous venez de définir]\n\n";
    
    // Informations de sécurité
    echo "🛡️  Notes de sécurité:\n";
    echo "   • Ce hash est unique (salt automatique)\n";
    echo "   • Impossible de retrouver le mot de passe original\n";
    echo "   • Résistant aux attaques par force brute (coût 12)\n";
    echo "   • Conforme aux standards OWASP\n\n";
    
    echo "✨ Opération terminée avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "💡 Vérifiez que PHP dispose des extensions nécessaires (password_hash)\n";
    exit(1);
}
?>
