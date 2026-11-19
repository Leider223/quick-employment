<?php
// =====================================================================
// modulos/usuarios/perfil.php
// CU03 - Consultar perfil de freelancer (RF04)
//
// El RF04 pide mostrar el perfil del freelancer con SUS HABILIDADES,
// EXPERIENCIA, SERVICIOS, DISPONIBILIDAD y CALIFICACIONES. Por eso esta
// página hace cinco consultas, cada una a su tabla:
//   1. perfil      -> funciones_perfil.php (nombre, presentación, experiencia)
//   2. habilidades -> funciones_perfil.php (tabla freelancer_habilidades)
//   3. servicios   -> los que están en estado 'activo'
//   4. disponibilidad -> días y horarios (tabla disponibilidad)
//   5. calificaciones -> notas de los clientes (tabla calificaciones)
//
// Flujo alterno del CU03: "Si el perfil ya no está disponible, el sistema
// muestra un mensaje". Se cumple cuando el id no existe o no es freelancer.
// =====================================================================

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';
require_once __DIR__ . '/funciones_perfil.php';

requerir_login();

$id_freelancer = (int) ($_GET['id'] ?? 0);

$perfil      = obtener_perfil_freelancer($pdo, $id_freelancer);
$habilidades = $perfil ? obtener_habilidades_freelancer($pdo, $id_freelancer) : [];

$servicios = [];
$disponibilidad = [];
$calificaciones = [];

if ($perfil) {
    // Servicios publicados y vigentes
    $consulta = $pdo->prepare(
        'SELECT s.id_servicio, s.titulo, s.descripcion, s.precio, c.nombre AS categoria
         FROM servicios s
         INNER JOIN categorias c ON c.id_categoria = s.id_categoria
         WHERE s.id_freelancer = :id AND s.estado = :estado
         ORDER BY s.fecha_publicacion DESC'
    );
    $consulta->execute([':id' => $id_freelancer, ':estado' => 'activo']);
    $servicios = $consulta->fetchAll();

    // Días y horarios. FIELD() ordena los días de lunes a domingo y no
    // alfabéticamente, que es lo que espera quien lee el perfil.
    $consulta = $pdo->prepare(
        "SELECT dia, hora_inicio, hora_fin
         FROM disponibilidad
         WHERE id_freelancer = :id
         ORDER BY FIELD(dia, 'lunes', 'martes', 'miercoles', 'jueves',
                        'viernes', 'sabado', 'domingo'), hora_inicio"
    );
    $consulta->execute([':id' => $id_freelancer]);
    $disponibilidad = $consulta->fetchAll();

    // Calificaciones: se llega a ellas por la solicitud y el servicio
    $consulta = $pdo->prepare(
        'SELECT cal.puntuacion, cal.comentario, cal.fecha,
                u.nombre AS cliente, s.titulo AS servicio
         FROM calificaciones cal
         INNER JOIN solicitudes sol ON sol.id_solicitud = cal.id_solicitud
         INNER JOIN servicios s     ON s.id_servicio   = sol.id_servicio
         INNER JOIN usuarios u      ON u.id_usuario    = sol.id_cliente
         WHERE s.id_freelancer = :id
         ORDER BY cal.fecha DESC'
    );
    $consulta->execute([':id' => $id_freelancer]);
    $calificaciones = $consulta->fetchAll();
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

abrir_pagina('Perfil del freelancer', $pdo);
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
