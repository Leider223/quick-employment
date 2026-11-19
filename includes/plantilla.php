<?php
// =====================================================================
// includes/plantilla.php
// Encabezado y pie comunes a todas las páginas.
//
// Para qué sirve: que el menú, los estilos y el pie estén en UN solo
// archivo. Si mañana cambia el nombre del sistema o se agrega un enlace,
// se cambia aquí y no en las 10 páginas.
//
// Cómo se usa en cualquier página:
//     require_once __DIR__ . '/../../includes/plantilla.php';
//     abrir_pagina('Mi perfil', $pdo);   // abre <html> y el menú
//     ...contenido...
//     cerrar_pagina();                   // cierra <main> y el pie
//
// El segundo parámetro es opcional: si se pasa la conexión, el menú
// muestra cuántas notificaciones hay sin leer (CU13).
// =====================================================================

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/notificaciones.php';

function abrir_pagina(string $titulo, ?PDO $pdo = null): void
{
    $logueado = usuario_logueado();
    $rol      = $_SESSION['rol'] ?? '';
    $no_leidas = 0;

    if ($pdo !== null && $logueado) {
        $no_leidas = contar_no_leidas($pdo, (int) $_SESSION['id_usuario']);
    }

    // Las rutas del menú siempre empiezan en URL_BASE para que funcionen
    // desde cualquier carpeta (modulos/usuarios, modulos/solicitudes, etc.).
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

function cerrar_pagina(): void
{
    ?>
    </main>

    <footer class="pie">
        Quick Employment - Proyecto de aula de Análisis y Diseño de Software
    </footer>
</body>
</html>
    <?php
}
