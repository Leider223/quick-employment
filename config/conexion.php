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
