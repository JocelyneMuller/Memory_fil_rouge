<?php
/**
 * Outil de diagnostic pour vérification des mots de passe
 * 
 * SÉCURITÉ: Accepte les credentials via CLI pour éviter l'exposition
 * Usage: php test_password.php "email@example.com" "motdepasse"
 * 
 * @author Memory Project
 * @version 2.0 - Sécurisé
 */

require_once 'config/database.php';

// Vérification d'exécution en ligne de commande uniquement
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('❌ Ce script ne peut être exécuté qu\'en ligne de commande pour des raisons de sécurité.');
}

// Validation des arguments CLI
if ($argc !== 3) {
    echo "❌ Usage incorrect\n";
    echo "📖 Syntaxe: php test_password.php \"email@example.com\" \"motdepasse\"\n";
    echo "📝 Exemple: php test_password.php \"admin@memory.local\" \"MemorySecure2024!\"\n\n";
    echo "⚠️  IMPORTANT:\n";
    echo "   • Utilisez des guillemets pour protéger les caractères spéciaux\n";
    echo "   • Les credentials ne sont pas stockés ou loggés\n";
    echo "   • Utilisez uniquement pour le diagnostic en développement\n";
    exit(1);
}

// Récupération sécurisée des paramètres
$email = filter_var(trim($argv[1]), FILTER_SANITIZE_EMAIL);
$password = $argv[2]; // Pas de sanitisation pour préserver les caractères spéciaux

// Validation de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Format d'email invalide: $email\n";
    echo "📝 Exemple valide: admin@memory.local\n";
    exit(1);
}

echo "🔍 Diagnostic de connexion\n";
echo str_repeat("━", 50) . "\n";
echo "📧 Email testé: $email\n";
echo "🕐 Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("━", 50) . "\n\n";

try {
    // Connexion à la base de données avec gestion d'erreurs
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // Requête préparée pour éviter l'injection SQL
    $stmt = $db->prepare('SELECT 
        id_User, 
        Username,
        Email_Unique, 
        Password_hash, 
        Role
        FROM User 
        WHERE Email_Unique = ?');
    
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête');
    }
    
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ RÉSULTAT: Utilisateur non trouvé\n";
        echo "📝 Email recherché: $email\n";
        echo "💡 Vérifiez que l'email existe dans la table User\n\n";
        
        // Suggestion diagnostique
        echo "🔍 Diagnostic suggéré:\n";
        echo "   • Vérifiez la base de données 'Memory'\n";
        echo "   • Consultez la table 'User'\n";
        echo "   • Vérifiez la colonne 'Email_Unique'\n";
        exit(1);
    }
    
    echo "✅ Utilisateur trouvé dans la base de données\n";
    echo "🆔 ID: " . $user['id_User'] . "\n";
    echo "👤 Username: " . $user['Username'] . "\n";
    echo "📧 Email: " . $user['Email_Unique'] . "\n";
    echo "🏷️  Rôle: " . $user['Role'] . "\n";
    echo "🔑 Hash stocké: " . substr($user['Password_hash'], 0, 20) . "...\n\n";
    
    // Vérification du mot de passe avec password_verify
    echo "🔐 Test de vérification du mot de passe...\n";
    
    $isValid = password_verify($password, $user['Password_hash']);
    
    if ($isValid) {
        echo "✅ SUCCÈS: Le mot de passe correspond!\n";
        echo "🎉 La connexion devrait fonctionner avec ces identifiants\n\n";
        
        echo "🧪 Informations de test:\n";
        echo "   🌐 URL de connexion: http://localhost:5173/login\n";
        echo "   📧 Email: " . $user['Email_Unique'] . "\n";
        echo "   🔑 Mot de passe: [celui que vous venez de tester]\n";
        echo "   👤 Rôle attendu: " . $user['Role'] . "\n\n";
        
        // Informations techniques sur le hash
        echo "🔬 Détails techniques du hash:\n";
        echo "   📏 Longueur: " . strlen($user['Password_hash']) . " caractères\n";
        echo "   🛡️  Algorithme: " . (password_get_info($user['Password_hash'])['algoName'] ?? 'Unknown') . "\n";
        echo "   💰 Coût bcrypt: " . (password_get_info($user['Password_hash'])['options']['cost'] ?? 'N/A') . "\n";
        
    } else {
        echo "❌ ÉCHEC: Le mot de passe ne correspond pas\n";
        echo "🔍 Le hash stocké ne correspond pas au mot de passe fourni\n\n";
        
        echo "🛠️  Solutions possibles:\n";
        echo "   1. 🔑 Vérifiez l'orthographe du mot de passe\n";
        echo "   2. 🔄 Générez un nouveau hash avec generate_password_hash.php\n";
        echo "   3. 🗄️  Mettez à jour la base de données avec le nouveau hash\n";
        echo "   4. 🧪 Testez à nouveau avec ce script\n\n";
        
        echo "📝 Commande pour générer un nouveau hash:\n";
        echo "   php generate_password_hash.php \"VotreNouveauMotDePasse\"\n";
    }
    
    echo "\n" . str_repeat("━", 50) . "\n";
    echo "🏁 Test terminé\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "🔧 Vérifications à effectuer:\n";
    echo "   • Base de données accessible\n";
    echo "   • Table 'User' existe\n";
    echo "   • Colonnes requises présentes\n";
    echo "   • Permissions de lecture accordées\n";
    exit(1);
}
