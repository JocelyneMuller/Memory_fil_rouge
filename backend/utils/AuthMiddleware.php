<?php
/**
 * Middleware d'authentification JWT
 * 
 * SÉCURITÉ: Valide les tokens JWT pour protéger les endpoints API
 * 
 * @author Memory Project
 * @version 1.0
 */

require_once __DIR__ . '/../config/jwt.php';

class AuthMiddleware {
    
    /**
     * Valide le token JWT présent dans les headers
     * 
     * @return array|false Données utilisateur si valide, false sinon
     */
    public static function validateToken() {
        try {
            // Récupération du header Authorization (compatible Apache/MAMP)
            $authHeader = null;
            
            // Méthode 1: getallheaders() (préférable)
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                foreach ($headers as $key => $value) {
                    if (strtolower($key) === 'authorization') {
                        $authHeader = $value;
                        break;
                    }
                }
            }
            
            // Méthode 2: $_SERVER (fallback pour Apache/MAMP)
            if (!$authHeader) {
                if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
                } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
                }
            }
            
            if (!$authHeader) {
                return false;
            }
            
            // Extraction du token (format: "Bearer TOKEN")
            if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return false;
            }
            
            $token = $matches[1];
            
            if (!$token) {
                return false;
            }
            
            // Utilisation des classes JWT existantes
            $payload = JWTUtils::decode($token);
            
            // Validation du token avec JWTConfig
            JWTConfig::validateToken($payload);
            
            // Token valide, retour des données utilisateur
            return [
                'id_User' => $payload['user_id'] ?? null,
                'email' => $payload['email'] ?? null,
                'role' => $payload['role'] ?? null
            ];
            
        } catch (Exception $e) {
            error_log('Erreur validation JWT: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Middleware obligatoire pour les routes protégées
     * 
     * @return void Envoie une erreur 401 si non authentifié
     */
    public static function requireAuth() {
        $user = self::validateToken();
        
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Token manquant ou invalide',
                'code' => 'UNAUTHORIZED'
            ]);
            exit;
        }
        
        // Stockage des données utilisateur pour utilisation dans les contrôleurs
        $_SESSION['authenticated_user'] = $user;
        
        return $user;
    }
    
    /**
     * Middleware optionnel pour les routes publiques avec info utilisateur
     *
     * @return array|null Données utilisateur si authentifié, null sinon
     */
    public static function optionalAuth() {
        $user = self::validateToken();

        if ($user) {
            $_SESSION['authenticated_user'] = $user;
        }

        return $user;
    }

    /**
     * Obtenir l'ID de l'utilisateur actuellement authentifié
     *
     * @return int|null ID de l'utilisateur ou null si non authentifié
     */
    public static function getCurrentUserId() {
        if (isset($_SESSION['authenticated_user']['id_User'])) {
            return (int) $_SESSION['authenticated_user']['id_User'];
        }

        // Fallback : essayer de valider le token directement
        $user = self::validateToken();
        if ($user && isset($user['id_User'])) {
            return (int) $user['id_User'];
        }

        return null;
    }

    /**
     * Obtenir toutes les données de l'utilisateur actuellement authentifié
     *
     * @return array|null Données utilisateur ou null si non authentifié
     */
    public static function getCurrentUser() {
        if (isset($_SESSION['authenticated_user'])) {
            return $_SESSION['authenticated_user'];
        }

        // Fallback : valider le token directement
        return self::validateToken();
    }
}

/**
 * Fonction utilitaire pour l'encodage base64 URL-safe
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
?>