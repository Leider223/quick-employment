<?php

date_default_timezone_set('America/Bogota');

// Los errores de mysqli se manejan con or die, como en el taller de clase
mysqli_report(MYSQLI_REPORT_OFF);

// Conexion a la base de datos
$connection_obj = mysqli_connect("localhost", "root", "", "quick_employment");

if (!$connection_obj) {
    echo "Error No: " . mysqli_connect_errno();
    echo "Error Description: " . mysqli_connect_error();
    exit;
}

// utf8mb4 para que las tildes y la enie se guarden bien
mysqli_set_charset($connection_obj, "utf8mb4");

// Hora de Colombia
mysqli_query($connection_obj, "SET time_zone = '-05:00'");

// ---------------------------------------------------------------------
// TEMPORAL: algunos archivos todavia usan PDO. Este bloque se borra
// cuando terminemos de pasar todo el proyecto a mysqli.
// ---------------------------------------------------------------------
$pdo = new PDO(
    "mysql:host=localhost;dbname=quick_employment;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);
$pdo->exec("SET time_zone = '-05:00'");
