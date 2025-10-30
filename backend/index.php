<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
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
    default:
        require_once 'views/404.php';
        break;
}