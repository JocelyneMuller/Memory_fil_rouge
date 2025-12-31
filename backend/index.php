<?php
header('Content-Type: application/json');

// Gestion CORS pour Vite dev server - Autoriser toutes origines localhost en dev
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Debug: log l'origine reçue
error_log("CORS Origin received: " . $origin);

// En développement, autoriser toutes les origines localhost
if (!empty($origin) && strpos($origin, 'http://localhost:') === 0) {
    header("Access-Control-Allow-Origin: " . $origin);
} else {
    // Fallback pour les requêtes sans origin (curl, etc.)
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/database.php';

$loc = filter_input(INPUT_GET, 'loc') ?? 'home';

switch ($loc) {
    case 'home':
        require_once 'views/home.php';
        break;
        
    case 'auth':
        // Routes d'authentification (login, register, me, logout)
        require_once 'controllers/auth.php';
        $action = filter_input(INPUT_GET, 'action') ?? 'login';
        $controller = new AuthController($PDO);
        $controller->run($action);
        break;
        
    case 'projects':
        require_once 'controllers/projects.php';
        $controller = new ProjectsController($PDO);
        $data = $controller-> run();
        echo json_encode($data);
        break;
        
    case 'categories':
        // Protection par authentification JWT
        require_once 'utils/AuthMiddleware.php';
        AuthMiddleware::requireAuth();
        
        // Endpoint simple pour lister les catégories
        if ($PDO) {
            $stmt = $PDO->prepare("SELECT * FROM Category");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($categories);
        } else {
            echo json_encode(['error' => 'Database connection failed']);
        }
        break;
        
    default:
        require_once 'views/404.php';
        break;
}