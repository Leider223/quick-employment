<?php
// =====================================================================
// config/conexion.php
// ÚNICA conexión a la base de datos de Quick Employment.
// Todos los módulos la usan así:   require_once __DIR__ . '/../../config/conexion.php';
// Después de incluirla, la variable $pdo queda lista para hacer consultas.
// Responsable: Integrante 1
// =====================================================================

// Datos de conexión (valores por defecto de XAMPP en tu computador)
$host    = 'localhost';
$bd      = 'quick_employment';
$usuario = 'root';
$clave   = '';              // XAMPP trae root SIN contraseña por defecto

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$bd;charset=utf8mb4",
        $usuario,
        $clave,
        [
            // Si una consulta falla, PHP lanza una excepción (así vemos el error)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Los resultados llegan como arreglos con el nombre de la columna
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Usa consultas preparadas reales del servidor (más seguro)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Se guarda el detalle técnico en el registro de errores de PHP
    // y al usuario solo se le muestra un mensaje general.
    error_log('Error de conexión: ' . $e->getMessage());
    die('No se pudo conectar con la base de datos.');
}
