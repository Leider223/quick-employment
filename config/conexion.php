<?php

date_default_timezone_set('America/Bogota');


$host    = 'localhost';
$bd      = 'quick_employment';
$usuario = 'root';
$clave   = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$bd;charset=utf8mb4",
        $usuario,
        $clave,
        [

            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {

    error_log('Error de conexión: ' . $e->getMessage());
    die('No se pudo conectar con la base de datos.');
}
