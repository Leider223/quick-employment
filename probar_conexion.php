<?php
// =====================================================================
// probar_conexion.php  (archivo TEMPORAL de prueba)
// Sirve solo para comprobar que PHP, XAMPP y la base funcionan juntos.
// Se borra del proyecto cuando todos hayan comprobado su conexión.
// =====================================================================
require_once __DIR__ . '/config/conexion.php';

// Consulta preparada: se cuentan los usuarios y se lista cada rol
$total = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

$sentencia = $pdo->prepare('SELECT nombre, correo, rol FROM usuarios WHERE rol = :rol');
$sentencia->execute([':rol' => 'freelancer']);
$freelancers = $sentencia->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de conexión - Quick Employment</title>
</head>
<body>
    <h1>Conexión exitosa</h1>
    <p>La base de datos <strong>quick_employment</strong> respondió correctamente.</p>
    <p>Usuarios registrados: <strong><?= (int) $total ?></strong></p>

    <h2>Freelancers de prueba</h2>
    <ul>
        <?php foreach ($freelancers as $f): ?>
            <li><?= htmlspecialchars($f['nombre']) ?> (<?= htmlspecialchars($f['correo']) ?>)</li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
