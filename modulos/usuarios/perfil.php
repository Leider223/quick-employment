<?php


require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';
require_once __DIR__ . '/funciones_perfil.php';

requerir_login();

$id_freelancer = (int) ($_GET['id'] ?? 0);

$perfil      = obtener_perfil_freelancer($connection_obj, $id_freelancer);
$habilidades = $perfil ? obtener_habilidades_freelancer($connection_obj, $id_freelancer) : [];

$servicios = [];
$disponibilidad = [];
$calificaciones = [];

if ($perfil) {
    // Servicios publicados y vigentes
    $consulta = mysqli_query(
        $connection_obj,
        "SELECT s.id_servicio, s.titulo, s.descripcion, s.precio, c.nombre AS categoria
         FROM servicios s
         INNER JOIN categorias c ON c.id_categoria = s.id_categoria
         WHERE s.id_freelancer = $id_freelancer AND s.estado = 'activo'
         ORDER BY s.fecha_publicacion DESC"
    ) or die(mysqli_error($connection_obj));

    $servicios = mysqli_fetch_all($consulta, MYSQLI_ASSOC);

    // Días y horarios. FIELD() ordena los días de lunes a domingo y no
    // alfabéticamente, que es lo que espera quien lee el perfil.
    $consulta = mysqli_query(
        $connection_obj,
        "SELECT dia, hora_inicio, hora_fin
         FROM disponibilidad
         WHERE id_freelancer = $id_freelancer
         ORDER BY FIELD(dia, 'lunes', 'martes', 'miercoles', 'jueves',
                        'viernes', 'sabado', 'domingo'), hora_inicio"
    ) or die(mysqli_error($connection_obj));

    $disponibilidad = mysqli_fetch_all($consulta, MYSQLI_ASSOC);

    // Calificaciones: se llega a ellas por la solicitud y el servicio
    $consulta = mysqli_query(
        $connection_obj,
        "SELECT cal.puntuacion, cal.comentario, cal.fecha,
                u.nombre AS cliente, s.titulo AS servicio
         FROM calificaciones cal
         INNER JOIN solicitudes sol ON sol.id_solicitud = cal.id_solicitud
         INNER JOIN servicios s     ON s.id_servicio   = sol.id_servicio
         INNER JOIN usuarios u      ON u.id_usuario    = sol.id_cliente
         WHERE s.id_freelancer = $id_freelancer
         ORDER BY cal.fecha DESC"
    ) or die(mysqli_error($connection_obj));

    $calificaciones = mysqli_fetch_all($consulta, MYSQLI_ASSOC);
}

// Promedio de las notas (se calcula en PHP porque ya tenemos la lista)
$promedio = 0.0;
if (!empty($calificaciones)) {
    $suma = 0;
    foreach ($calificaciones as $calificacion) {
        $suma += (int) $calificacion['puntuacion'];
    }
    $promedio = $suma / count($calificaciones);
}

// Nombres de los días con tilde, para mostrarlos bonitos
$nombres_dia = [
    'lunes'     => 'Lunes',
    'martes'    => 'Martes',
    'miercoles' => 'Miércoles',
    'jueves'    => 'Jueves',
    'viernes'   => 'Viernes',
    'sabado'    => 'Sábado',
    'domingo'   => 'Domingo',
];

abrir_pagina('Perfil del freelancer', $connection_obj);
?>

<?php if (!$perfil): ?>
    <div class="tarjeta">
        <h1>Perfil del freelancer</h1>
        <div class="alerta alerta-aviso">Ese perfil no existe o ya no está disponible.</div>
        <a class="boton" href="../servicios/buscar.php">Volver a la búsqueda</a>
    </div>
<?php else: ?>

    <div class="tarjeta">
        <h1><?= htmlspecialchars($perfil['nombre']) ?></h1>

        <?php if ((int) $perfil['disponible']): ?>
            <span class="estado estado-aceptada">Disponible</span>
        <?php else: ?>
            <span class="estado estado-cancelada">No disponible</span>
        <?php endif; ?>

        <?php if (!empty($calificaciones)): ?>
            <p class="meta">
                <?= number_format($promedio, 1, ',', '.') ?> de 5
                (<?= count($calificaciones) ?> calificación<?= count($calificaciones) === 1 ? '' : 'es' ?>)
            </p>
        <?php else: ?>
            <p class="meta">Todavía no tiene calificaciones.</p>
        <?php endif; ?>

        <h2>Presentación</h2>
        <p><?= nl2br(htmlspecialchars($perfil['presentacion'] ?? 'Sin presentación.')) ?></p>

        <h2>Experiencia</h2>
        <p><?= nl2br(htmlspecialchars($perfil['experiencia'] ?? 'Sin experiencia registrada.')) ?></p>
    </div>

    <div class="tarjeta">
        <h2>Habilidades</h2>
        <?php if (empty($habilidades)): ?>
            <p>Todavía no ha registrado habilidades.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($habilidades as $habilidad): ?>
                    <li><?= htmlspecialchars($habilidad['nombre']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="tarjeta">
        <h2>Servicios</h2>
        <?php if (empty($servicios)): ?>
            <p>No tiene servicios publicados en este momento.</p>
        <?php else: ?>
            <?php foreach ($servicios as $servicio): ?>
                <div class="resultado">
                    <h2><?= htmlspecialchars($servicio['titulo']) ?></h2>
                    <p class="meta">
                        <?= htmlspecialchars($servicio['categoria']) ?> ·
                        <span class="precio">$<?= number_format($servicio['precio'], 0, ',', '.') ?></span>
                    </p>
                    <p><?= nl2br(htmlspecialchars($servicio['descripcion'])) ?></p>

                    <?php if ($_SESSION['rol'] === 'cliente' && (int) $perfil['disponible']): ?>
                        <a class="boton"
                           href="../solicitudes/nueva_solicitud.php?id_servicio=<?= (int) $servicio['id_servicio'] ?>">
                            Contratar
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="tarjeta">
        <h2>Disponibilidad</h2>
        <?php if (empty($disponibilidad)): ?>
            <p>No ha indicado horarios.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($disponibilidad as $franja): ?>
                    <li>
                        <?= $nombres_dia[$franja['dia']] ?? $franja['dia'] ?>:
                        <?= substr($franja['hora_inicio'], 0, 5) ?> -
                        <?= substr($franja['hora_fin'], 0, 5) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="tarjeta">
        <h2>Calificaciones</h2>
        <?php if (empty($calificaciones)): ?>
            <p>Todavía no tiene calificaciones de sus clientes.</p>
        <?php else: ?>
            <?php foreach ($calificaciones as $calificacion): ?>
                <div class="resultado">
                    <p>
                        <strong><?= (int) $calificacion['puntuacion'] ?> / 5</strong>
                        · <?= htmlspecialchars($calificacion['servicio']) ?>
                        · <?= fecha_corta($calificacion['fecha']) ?>
                    </p>
                    <p class="meta">Por <?= htmlspecialchars($calificacion['cliente']) ?></p>
                    <?php if ($calificacion['comentario'] !== null && $calificacion['comentario'] !== ''): ?>
                        <p><?= nl2br(htmlspecialchars($calificacion['comentario'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php cerrar_pagina(); ?>
