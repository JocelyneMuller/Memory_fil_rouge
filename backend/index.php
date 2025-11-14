<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:5173");
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
    case 'projects':
        require_once 'controllers/projects.php';
        $controller = new ProjectsController($PDO);
        $data = $controller-> run();
        echo json_encode($data);
        break;
    case 'categories':
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