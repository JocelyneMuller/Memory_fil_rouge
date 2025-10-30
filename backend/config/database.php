<?php
$host = 'localhost';
$db = 'Memory';
$user = 'root';
$pass = 'root';

$PDO = null;
try {

    $PDO = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed ' . $e->getMessage();
}
