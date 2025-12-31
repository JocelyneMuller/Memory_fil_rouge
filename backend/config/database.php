<?php
$db = 'Memory';
$user = 'root';
$pass = 'root';

// MAMP utilise un socket Unix pour MySQL
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';

$PDO = null;
try {
    // Connexion via socket Unix (méthode MAMP)
    $PDO = new PDO("mysql:unix_socket=$socket;dbname=$db;charset=utf8", $user, $pass);
    $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    // Ne pas lever l'exception pour permettre au code de continuer (gestion d'erreur côté contrôleur)
    if (php_sapi_name() === 'cli') {
        echo 'Database connection failed: ' . $e->getMessage() . "\n";
        echo 'Make sure MAMP MySQL is running and socket exists at: ' . $socket . "\n";
    }
}

/**
 * Retourne l'instance PDO ou lève une exception si la connexion n'est pas établie.
 * Utilise la variable $PDO initialisée ci‑dessus.
 */
function getDBConnection() {
    global $PDO, $socket;
    if ($PDO instanceof PDO) {
        return $PDO;
    }
    // Comportement clair en CLI pour debug
    $msg = 'Database connection not established. Ensure MAMP MySQL is running and socket exists: ' . $socket;
    if (php_sapi_name() === 'cli') {
        echo $msg . PHP_EOL;
    }
    throw new Exception($msg);
}
?>