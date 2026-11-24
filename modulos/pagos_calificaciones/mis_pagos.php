<?php

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_rol('freelancer');

$idFreelancer = (int) $_SESSION['id_usuario'];

// Pagos que le hicieron a este freelancer
$consultaPagos = mysqli_query(
    $connection_obj,
    "SELECT p.id_pago, p.valor, p.metodo, p.fecha_pago,
            sol.id_solicitud, s.titulo, u.nombre AS cliente
     FROM pagos p
     INNER JOIN solicitudes sol ON sol.id_solicitud = p.id_solicitud
     INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
     INNER JOIN usuarios u ON u.id_usuario = sol.id_cliente
     WHERE s.id_freelancer = $idFreelancer
     ORDER BY p.fecha_pago DESC, p.id_pago DESC"
) or die(mysqli_error($connection_obj));

$pagos = mysqli_fetch_all($consultaPagos, MYSQLI_ASSOC);

// Calificaciones que le dejaron
$consultaCalificaciones = mysqli_query(
    $connection_obj,
    "SELECT cal.puntuacion, cal.comentario, cal.fecha,
            sol.id_solicitud, s.titulo, u.nombre AS cliente
     FROM calificaciones cal
     INNER JOIN solicitudes sol ON sol.id_solicitud = cal.id_solicitud
     INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
     INNER JOIN usuarios u ON u.id_usuario = sol.id_cliente
     WHERE s.id_freelancer = $idFreelancer
     ORDER BY cal.fecha DESC"
) or die(mysqli_error($connection_obj));

$calificaciones = mysqli_fetch_all($consultaCalificaciones, MYSQLI_ASSOC);

// Sumas para mostrar el total y el promedio
$totalRecibido = 0;
foreach ($pagos as $pago) {
    $totalRecibido += (float) $pago['valor'];
}

$sumaNotas = 0;
foreach ($calificaciones as $calificacion) {
    $sumaNotas += (int) $calificacion['puntuacion'];
}

$promedio = count($calificaciones) > 0 ? $sumaNotas / count($calificaciones) : 0;

abrir_pagina('Mis pagos y calificaciones', $connection_obj);
?>

<div class="tarjeta">
    <h1>Mis pagos y calificaciones</h1>

    <p class="meta">
        Total recibido: <strong>$<?= number_format($totalRecibido, 0, ',', '.') ?></strong>
        · <?= count($pagos) ?> pago<?= count($pagos) === 1 ? '' : 's' ?>
        · Promedio: <strong><?= number_format($promedio, 1, ',', '.') ?> / 5</strong>
        (<?= count($calificaciones) ?> calificación<?= count($calificaciones) === 1 ? '' : 'es' ?>)
    </p>
</div>

<div class="tarjeta">
    <h2>Pagos recibidos</h2>

    <?php if (empty($pagos)): ?>
        <div class="alerta alerta-aviso">Todavía no has recibido pagos.</div>
    <?php else: ?>
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Servicio</th>
                        <th>Cliente</th>
                        <th>Valor</th>
                        <th>Método</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago): ?>
                        <tr>
                            <td><?= (int) $pago['id_solicitud'] ?></td>
                            <td><?= htmlspecialchars($pago['titulo']) ?></td>
                            <td><?= htmlspecialchars($pago['cliente']) ?></td>
                            <td>$<?= number_format((float) $pago['valor'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($pago['metodo']) ?></td>
                            <td><?= fecha_corta($pago['fecha_pago']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="tarjeta">
    <h2>Calificaciones recibidas</h2>

    <?php if (empty($calificaciones)): ?>
        <div class="alerta alerta-aviso">Todavía no te han calificado.</div>
    <?php else: ?>
        <?php foreach ($calificaciones as $calificacion): ?>
            <div class="resultado">
                <p>
                    <strong><?= (int) $calificacion['puntuacion'] ?> / 5</strong>
                    · <?= htmlspecialchars($calificacion['titulo']) ?>
                    · <?= fecha_corta($calificacion['fecha']) ?>
                </p>
                <p class="meta">Por <?= htmlspecialchars($calificacion['cliente']) ?></p>
                <?php if ($calificacion['comentario'] !== null && $calificacion['comentario'] !== ''): ?>
                    <p><?= nl2br(htmlspecialchars($calificacion['comentario'])) ?></p>
                <?php endif; ?>
                <a href="../solicitudes/detalle.php?id=<?= (int) $calificacion['id_solicitud'] ?>">Ver la solicitud</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php cerrar_pagina(); ?>
