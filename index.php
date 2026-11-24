<?php


require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/plantilla.php';

$logueado = usuario_logueado();
$rol      = $_SESSION['rol'] ?? '';

// Tres servicios activos recientes, solo para que la portada no quede vacía
$resultado = mysqli_query(
    $connection_obj,
    "SELECT s.id_servicio, s.titulo, s.precio, u.nombre AS freelancer
     FROM servicios s
     INNER JOIN usuarios u ON u.id_usuario = s.id_freelancer
     WHERE s.estado = 'activo'
     ORDER BY s.fecha_publicacion DESC
     LIMIT 3"
) or die(mysqli_error($connection_obj));

$recientes = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

abrir_pagina('Inicio', $connection_obj);
?>

<div class="tarjeta">
    <h1>Quick Employment</h1>
    <p>
        Plataforma que conecta a personas que necesitan un servicio
        (<strong>clientes</strong>) con personas que tienen las habilidades para
        ofrecerlo (<strong>freelancers</strong>).
    </p>

    <?php if ($logueado): ?>
        <p>Hola, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>
            (<?= $rol === 'cliente' ? 'cliente' : 'freelancer' ?>).</p>

        <?php if ($rol === 'cliente'): ?>
            <a class="boton" href="modulos/servicios/buscar.php">Buscar servicios</a>
            <a class="boton boton-secundario" href="modulos/solicitudes/mis_solicitudes.php">Mis solicitudes</a>
        <?php else: ?>
            <a class="boton" href="modulos/solicitudes/recibidas.php">Solicitudes recibidas</a>
            <a class="boton boton-secundario" href="modulos/servicios/mis_servicios.php">Mis servicios</a>
            <a class="boton boton-secundario" href="modulos/usuarios/disponibilidad.php">Mi disponibilidad</a>
            <a class="boton boton-secundario" href="modulos/usuarios/editar_perfil.php">Mi perfil</a>
        <?php endif; ?>

    <?php else: ?>
        <a class="boton" href="modulos/usuarios/registro.php">Crear cuenta</a>
        <a class="boton boton-secundario" href="modulos/usuarios/login.php">Iniciar sesión</a>
    <?php endif; ?>
</div>

<?php if ($logueado): ?>
<div class="tarjeta">
    <h2>Buscar un servicio</h2>
    <form method="get" action="modulos/servicios/buscar.php">
        <label for="q">¿Qué necesitas?</label>
        <input type="text" id="q" name="q" maxlength="100"
               placeholder="Por ejemplo: logo, página web, traducción">
        <button type="submit" class="boton">Buscar</button>
    </form>
</div>
<?php endif; ?>

<div class="tarjeta">
    <h2>Servicios publicados</h2>

    <?php if (empty($recientes)): ?>
        <p>Todavía no hay servicios publicados.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($recientes as $servicio): ?>
                <li>
                    <?php if ($logueado): ?>
                        <a href="modulos/servicios/buscar.php?q=<?= urlencode($servicio['titulo']) ?>">
                            <?= htmlspecialchars($servicio['titulo']) ?>
                        </a>
                    <?php else: ?>
                        <?= htmlspecialchars($servicio['titulo']) ?>
                    <?php endif; ?>
                    — <?= htmlspecialchars($servicio['freelancer']) ?>
                    (<span class="precio">$<?= number_format($servicio['precio'], 0, ',', '.') ?></span>)
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!$logueado): ?>
            <p><a href="modulos/usuarios/registro.php">Crea tu cuenta</a> para ver los perfiles y contratar.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php cerrar_pagina(); ?>
