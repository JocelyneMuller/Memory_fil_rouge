<?php

require_once 'config/database.php';

try {
    $db = getDBConnection();
    $stmt = $db->query('SELECT id_User, Username, Email_Unique, Role FROM User');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Erreur: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
?>
