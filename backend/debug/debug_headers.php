<?php
/**
 * Script de diagnostic pour vérifier la réception des en-têtes HTTP
 * Affiche tous les en-têtes reçus et les variables $_SERVER
 */

header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');

// Gestion preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$debug = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers_function_exists' => function_exists('getallheaders'),
    'server_vars' => [],
    'all_headers' => [],
    'authorization_found' => false,
    'authorization_value' => null,
    'authorization_location' => null
];

// Récupérer toutes les variables $_SERVER commençant par HTTP_
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0 || strpos($key, 'REDIRECT_') === 0) {
        $debug['server_vars'][$key] = $value;
        
        // Chercher spécifiquement Authorization
        if (stripos($key, 'AUTHORIZATION') !== false) {
            $debug['authorization_found'] = true;
            $debug['authorization_value'] = $value;
            $debug['authorization_location'] = $key;
        }
    }
}

// Essayer getallheaders() si disponible
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $debug['all_headers'] = $headers;
    
    // Chercher Authorization (insensible à la casse)
    foreach ($headers as $key => $value) {
        if (strcasecmp($key, 'Authorization') === 0) {
            if (!$debug['authorization_found']) {
                $debug['authorization_found'] = true;
                $debug['authorization_value'] = $value;
                $debug['authorization_location'] = 'getallheaders()';
            }
        }
    }
}

// Test direct des 3 méthodes
$debug['test_methods'] = [
    'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT FOUND',
    'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'NOT FOUND',
];

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
