<?php


require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/notificaciones.php';

function abrir_pagina($titulo, $con = null)
{
    $logueado = usuario_logueado();
    $rol = $_SESSION['rol'] ?? '';
    $no_leidas = 0;

    if ($con !== null && $logueado) {
        $no_leidas = contar_no_leidas($con, (int) $_SESSION['id_usuario']);
    }


    $base = URL_BASE;
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?> - Quick Employment</title>
    <link rel="stylesheet" href="<?= $base ?>/css/estilos.css">
</head>
<body>
    <header class="encabezado">
        <a class="logo" href="<?= $base ?>/index.php">Quick Employment</a>

        <nav>
            <?php if ($logueado): ?>
                <a href="<?= $base ?>/modulos/servicios/buscar.php">Buscar servicios</a>

                <?php if ($rol === 'cliente'): ?>
                    <a href="<?= $base ?>/modulos/solicitudes/mis_solicitudes.php">Mis solicitudes</a>
                <?php else: ?>
                    <a href="<?= $base ?>/modulos/solicitudes/recibidas.php">Solicitudes recibidas</a>
                    <a href="<?= $base ?>/modulos/usuarios/editar_perfil.php">Mi perfil</a>
                    <a href="<?= $base ?>/modulos/servicios/mis_servicios.php">Mis servicios</a>
                    <a href="<?= $base ?>/modulos/pagos_calificaciones/mis_pagos.php">Pagos y calificaciones</a>
                    <a href="<?= $base ?>/modulos/usuarios/disponibilidad.php">Mi disponibilidad</a>
                    <a href="<?= $base ?>/modulos/usuarios/editar_habilidades.php">Mis habilidades</a>
                <?php endif; ?>

                <a href="<?= $base ?>/modulos/solicitudes/notificaciones.php">
                    Notificaciones<?= $no_leidas > 0 ? ' (' . $no_leidas . ')' : '' ?>
                </a>
                <a href="<?= $base ?>/modulos/usuarios/logout.php">Salir</a>
            <?php else: ?>
                <a href="<?= $base ?>/modulos/usuarios/login.php">Iniciar sesión</a>
                <a href="<?= $base ?>/modulos/usuarios/registro.php">Crear cuenta</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="contenedor">
    <?php
}

function cerrar_pagina()
{
    ?>
    </main>

    <footer class="pie">
        Quick Employment
    </footer>
</body>
</html>
    <?php
}
