<?php
/**
 * Configuration JWT pour l'authentification Memory
 * 
 * Ce fichier contient la configuration sécurisée pour les tokens JWT :
 * - Clé secrète pour signer les tokens (stockée en variable d'environnement en production)
 * - Paramètres de sécurité (expiration, algorithme, validation)
 * - Utilitaires pour encoder/décoder les tokens JWT
 * 
 * SÉCURITÉ : La clé secrète DOIT être stockée dans les variables d'environnement
 * en production et jamais commitée dans le code source.
 */

/**
 * Configuration principale JWT
 * Centralise tous les paramètres de sécurité pour l'authentification
 */
class JWTConfig {
    
    /**
     * Clé secrète pour signer les tokens JWT
     * SÉCURITÉ : En production, cette valeur DOIT venir des variables d'environnement
     * La clé doit faire minimum 256 bits (32 caractères) pour HS256
     */
    private static $secret = 'memory_jwt_secret_key_2024_change_in_production_minimum_256_bits_required';
    
    /**
     * Algorithme de signature JWT
     * HS256 (HMAC SHA-256) est recommandé pour la plupart des applications
     */
    public static $algorithm = 'HS256';
    
    /**
     * Durée de validité du token en secondes
     * 86400 = 24 heures (équilibre entre sécurité et expérience utilisateur)
     */
    public static $expiration = 86400;
    
    /**
     * Issuer (émetteur) du token - identifie l'application Memory
     */
    public static $issuer = 'memory_app';
    
    /**
     * Audience - qui peut utiliser ce token
     */
    public static $audience = 'memory_users';
    
    /**
     * Génère un payload JWT standard avec claims sécurisés
     * 
     * @param array $userData - Données utilisateur depuis la base
     * @return array Payload JWT avec claims standard et custom
     */
    public static function generatePayload($userData) {
        $now = time();
        
        return [
            // Claims standards (registered claims) pour la sécurité
            'iss' => self::$issuer,           // Issuer - qui a émis le token
            'aud' => self::$audience,         // Audience - pour qui est le token  
            'iat' => $now,                    // Issued At - quand le token a été créé
            'exp' => $now + self::$expiration, // Expiration - quand le token expire
            'nbf' => $now,                    // Not Before - token valide à partir de maintenant
            
            // Claims custom pour l'application Memory
            'user_id' => (int)$userData['id_User'],
            'username' => $userData['Username'], 
            'email' => $userData['Email_Unique'],
            'role' => $userData['Role']
        ];
    }
    
    /**
     * Valide les claims d'un token décodé contre les attaques
     * 
     * @param array $payload - Payload décodé du JWT
     * @return bool True si le token est valide
     * @throws Exception Si le token est invalide (avec message spécifique)
     */
    public static function validateToken($payload) {
        $now = time();
        
        // Protection contre les tokens forgés - vérifier l'issuer
        if (!isset($payload['iss']) || $payload['iss'] !== self::$issuer) {
            throw new Exception('Token issuer invalide - possible tentative de forgerie');
        }
        
        // Protection contre l'utilisation non autorisée - vérifier l'audience
        if (!isset($payload['aud']) || $payload['aud'] !== self::$audience) {
            throw new Exception('Token audience invalide - utilisation non autorisée');
        }
        
        // Protection contre les tokens expirés
        if (!isset($payload['exp']) || $payload['exp'] < $now) {
            throw new Exception('Token expiré - veuillez vous reconnecter');
        }
        
        // Protection contre les tokens prématurés
        if (isset($payload['nbf']) && $payload['nbf'] > $now) {
            throw new Exception('Token pas encore valide');
        }
        
        // Vérification que tous les claims requis sont présents
        $requiredClaims = ['user_id', 'username', 'email', 'role'];
        foreach ($requiredClaims as $claim) {
            if (!isset($payload[$claim])) {
                throw new Exception("Claim requis manquant : $claim");
            }
        }
        
        return true;
    }
    
    /**
     * Récupère la clé secrète de manière sécurisée
     * SÉCURITÉ : Priorité aux variables d'environnement pour la production
     * 
     * @return string Clé secrète pour signer les tokens
     * @throws Exception Si aucune clé n'est configurée en production
     */
    public static function getSecret() {
        // En production, utiliser une variable d'environnement sécurisée
        $envSecret = getenv('JWT_SECRET') ?: $_ENV['JWT_SECRET'] ?? null;
        
        if ($envSecret) {
            return $envSecret;
        }
        
        // En développement local, utiliser la clé par défaut avec warning
        $isLocalhost = isset($_SERVER['HTTP_HOST']) && 
                      (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                       strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
        
        if ($isLocalhost) {
            error_log('WARNING: Utilisation de la clé JWT par défaut en développement');
            return self::$secret;
        }
        
        // En production sans variable d'environnement = erreur critique
        throw new Exception('JWT_SECRET doit être défini en production pour la sécurité');
    }
}

/**
 * Utilitaires JWT avec implémentation sécurisée native
 * Pas de dépendance externe pour éviter les vulnérabilités
 */
class JWTUtils {
    
    /**
     * Encode un payload en token JWT signé
     * Utilise HMAC SHA-256 pour la signature cryptographique
     * 
     * @param array $payload - Données à encoder dans le token
     * @return string Token JWT encodé et signé
     */
    public static function encode($payload) {
        // Header standard JWT avec algorithme sécurisé
        $header = [
            'typ' => 'JWT',
            'alg' => JWTConfig::$algorithm
        ];
        
        // Encodage Base64 URL-safe des parties du token
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        // Signature cryptographique HMAC SHA-256 pour l'intégrité
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWTConfig::getSecret(), true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Décode et valide un token JWT de manière sécurisée
     * 
     * @param string $token - Token JWT à décoder
     * @return array Payload décodé si le token est valide
     * @throws Exception Si le token est invalide, expiré ou falsifié
     */
    public static function decode($token) {
        $parts = explode('.', $token);
        
        // Protection contre les tokens malformés
        if (count($parts) !== 3) {
            throw new Exception('Format de token JWT invalide');
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Vérification de la signature pour détecter les tentatives de falsification
        $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWTConfig::getSecret(), true);
        $expectedSignatureEncoded = self::base64UrlEncode($expectedSignature);
        
        // Comparaison temporellement constante contre les attaques timing
        if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
            throw new Exception('Signature JWT invalide - token possiblement falsifié');
        }
        
        // Décodage sécurisé du payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            throw new Exception('Payload JWT invalide - données corrompues');
        }
        
        // Validation des claims de sécurité
        JWTConfig::validateToken($payload);
        
        return $payload;
    }
    
    /**
     * Encodage Base64 URL-safe (sans padding)
     * Requis par le standard RFC 7515 pour JWT
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Décodage Base64 URL-safe
     * Gère le padding manquant automatiquement
     */
    private static function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
?>
