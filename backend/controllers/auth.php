<?php
/**
 * Contrôleur d'authentification - Gestion de la connexion et inscription
 * 
 * Ce contrôleur gère toutes les routes liées à l'authentification :
 * - POST /auth/login : Connexion d'un utilisateur
 * - POST /auth/register : Inscription d'un nouvel utilisateur (admin uniquement)
 * - GET /auth/me : Récupérer les informations de l'utilisateur connecté
 * - POST /auth/logout : Déconnexion (côté client surtout)
 * 
 * SÉCURITÉ :
 * - Validation stricte des entrées
 * - Génération de tokens JWT sécurisés
 * - Vérification des rôles pour les actions sensibles
 * - Protection contre les attaques brute-force (à implémenter)
 */

// Inclusion des dépendances
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../models/User_model.php';

class AuthController {
    
    /**
     * Connexion à la base de données
     * @var PDO
     */
    private $db;
    
    /**
     * Modèle User pour les opérations en base
     * @var User_model
     */
    private $userModel;
    
    /**
     * Constructeur - Injection de dépendance
     * 
     * @param PDO $db - Connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new User_model($db);
    }
    
    /**
     * Gestionnaire principal des routes d'authentification
     * Distribue les requêtes vers les bonnes méthodes selon l'action
     * 
     * @param string $action - Action demandée (login, register, me, logout)
     */
    public function run($action) {
        // Configuration CORS pour permettre les requêtes du frontend
        $this->setCorsHeaders();
        
        // Gestion des requêtes OPTIONS (preflight CORS)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Distribution vers la méthode appropriée
        switch ($action) {
            case 'login':
                $this->login();
                break;
                
            case 'register':
                $this->register();
                break;
                
            case 'me':
                $this->me();
                break;
                
            case 'logout':
                $this->logout();
                break;
                
            default:
                $this->sendError('Action non reconnue', 404);
                break;
        }
    }
    
    /**
     * LOGIN - Connexion d'un utilisateur
     * Route : POST /auth/login
     * Body : { "email": "user@example.com", "password": "password123" }
     * 
     * Process :
     * 1. Récupération des données (email, password)
     * 2. Validation du format
     * 3. Recherche de l'utilisateur en base
     * 4. Vérification du mot de passe
     * 5. Génération du token JWT
     * 6. Renvoi du token et des infos utilisateur
     */
    private function login() {
        // Vérifier que c'est bien une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Méthode non autorisée. Utilisez POST', 405);
            return;
        }
        
        // Récupération des données JSON du body
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validation des champs requis
        if (empty($data['email']) || empty($data['password'])) {
            $this->sendError('Email et mot de passe requis', 400);
            return;
        }
        
        $email = trim($data['email']);
        $password = $data['password'];
        
        // Validation du format email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendError('Format d\'email invalide', 400);
            return;
        }
        
        // Recherche de l'utilisateur par email
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            // Utilisateur non trouvé
            // Message générique pour ne pas révéler si l'email existe
            $this->sendError('Email ou mot de passe incorrect', 401);
            return;
        }
        
        // Vérification du mot de passe avec le hash stocké
        if (!$this->userModel->verifyPassword($password, $user['Password_hash'])) {
            // Mot de passe incorrect
            $this->sendError('Email ou mot de passe incorrect', 401);
            return;
        }
        
        // Authentification réussie - Génération du token JWT
        try {
            $payload = JWTConfig::generatePayload($user);
            $token = JWTUtils::encode($payload);
            
            // Préparer les données utilisateur à renvoyer (sans le hash)
            unset($user['Password_hash']);
            
            // Succès - Renvoi du token et des infos utilisateur
            $this->sendSuccess([
                'token' => $token,
                'user' => $user,
                'message' => 'Connexion réussie'
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur génération JWT : " . $e->getMessage());
            $this->sendError('Erreur lors de la connexion', 500);
        }
    }
    
    /**
     * REGISTER - Inscription d'un nouvel utilisateur
     * Route : POST /auth/register
     * Body : { "username": "john", "email": "john@example.com", "password": "pass123", "role": "user" }
     * 
     * SÉCURITÉ : Cette route doit être accessible UNIQUEMENT par un admin
     * 
     * Process :
     * 1. Vérification que l'utilisateur connecté est admin
     * 2. Validation des données
     * 3. Création du compte via le modèle
     * 4. Renvoi des infos du nouvel utilisateur
     */
    private function register() {
        // Vérifier que c'est bien une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Méthode non autorisée. Utilisez POST', 405);
            return;
        }
        
        // Vérifier que l'utilisateur connecté est admin
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser) {
            $this->sendError('Authentification requise', 401);
            return;
        }
        
        if ($currentUser['role'] !== 'admin') {
            $this->sendError('Accès refusé. Seul un admin peut créer des comptes', 403);
            return;
        }
        
        // Récupération des données JSON
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validation des champs requis
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            $this->sendError('Username, email et password requis', 400);
            return;
        }
        
        // Création de l'utilisateur via le modèle
        $result = $this->userModel->create([
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'password' => $data['password'],
            'role' => $data['role'] ?? 'user'  // Par défaut : user
        ]);
        
        // Vérification du résultat
        if ($result['success']) {
            $this->sendSuccess($result);
        } else {
            $this->sendError($result['error'], 400);
        }
    }
    
    /**
     * ME - Récupérer les informations de l'utilisateur connecté
     * Route : GET /auth/me
     * Header : Authorization: Bearer <token>
     * 
     * Permet au frontend de récupérer les infos de l'utilisateur
     * à partir du token JWT (au chargement de l'app par exemple)
     * 
     * Process :
     * 1. Extraction du token JWT depuis l'en-tête Authorization
     * 2. Décodage et validation du token
     * 3. Récupération des infos utilisateur depuis la base
     * 4. Renvoi des données
     */
    private function me() {
        // Vérifier que c'est bien une requête GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('Méthode non autorisée. Utilisez GET', 405);
            return;
        }
        
        // Récupérer l'utilisateur actuel depuis le token
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser) {
            $this->sendError('Token invalide ou expiré', 401);
            return;
        }
        
        // Récupérer les données complètes depuis la base
        $user = $this->userModel->findById($currentUser['user_id']);
        
        if (!$user) {
            $this->sendError('Utilisateur non trouvé', 404);
            return;
        }
        
        // Supprimer le hash du mot de passe
        unset($user['Password_hash']);
        
        // Succès
        $this->sendSuccess([
            'user' => $user
        ]);
    }
    
    /**
     * LOGOUT - Déconnexion de l'utilisateur
     * Route : POST /auth/logout
     * 
     * Note : Avec JWT, la déconnexion se fait principalement côté client
     * en supprimant le token du localStorage. Cette route est surtout
     * symbolique et peut être utilisée pour logger la déconnexion.
     */
    private function logout() {
        // Avec JWT, la déconnexion est gérée côté client
        // (suppression du token dans localStorage)
        
        // Cette route peut être utilisée pour :
        // - Logger la déconnexion dans les logs
        // - Invalider le token dans une blacklist (avancé)
        // - Nettoyer des données de session côté serveur si nécessaire
        
        $this->sendSuccess([
            'message' => 'Déconnexion réussie'
        ]);
    }
    
    /**
     * Récupère l'utilisateur actuel depuis le token JWT
     * Extrait le token de l'en-tête Authorization et le décode
     * 
     * @return array|null Payload du token ou null si invalide
     */
    private function getCurrentUser() {
        // Récupérer l'en-tête Authorization (compatible Apache/MAMP)
        $authHeader = '';
        
        // Méthode 1 : Header Authorization standard
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } 
        // Méthode 2 : Apache avec mod_rewrite
        elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        // Méthode 3 : getallheaders() si disponible
        elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        
        // Vérifier le format "Bearer <token>"
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }
        
        $token = $matches[1];
        
        // Décoder et valider le token
        try {
            $payload = JWTUtils::decode($token);
            
            // Valider le token avec JWTConfig
            JWTConfig::validateToken($payload);
            
            return $payload;
            
        } catch (Exception $e) {
            error_log("Erreur décodage/validation token : " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Configure les en-têtes CORS pour autoriser les requêtes du frontend
     * Permet au frontend Vue.js de communiquer avec l'API
     */
    private function setCorsHeaders() {
        // Autoriser les requêtes depuis le frontend Vue.js (tous ports localhost)
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        if (!empty($origin) && strpos($origin, 'http://localhost:') === 0) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            header('Access-Control-Allow-Origin: *');
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('Content-Type: application/json; charset=UTF-8');
    }
    
    /**
     * Envoie une réponse de succès au format JSON
     * 
     * @param array $data - Données à renvoyer
     * @param int $statusCode - Code HTTP (200 par défaut)
     */
    private function sendSuccess($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }
    
    /**
     * Envoie une réponse d'erreur au format JSON
     * 
     * @param string $message - Message d'erreur
     * @param int $statusCode - Code HTTP (400 par défaut)
     */
    private function sendError($message, $statusCode = 400) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
        exit;
    }
}

/**
 * Point d'entrée du contrôleur
 * Instancie le contrôleur et distribue vers la bonne action
 */
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    // Ce fichier est appelé directement
    require_once __DIR__ . '/../config/database.php';
    
    // Récupérer l'action depuis les paramètres GET
    $action = $_GET['action'] ?? 'login';
    
    // Instancier le contrôleur avec la connexion DB
    $controller = new AuthController($db);
    
    // Exécuter l'action demandée
    $controller->run($action);
}
?>
