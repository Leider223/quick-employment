<?php


require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_rol('freelancer');

$id_freelancer = (int) $_SESSION['id_usuario'];

$consulta = mysqli_query(
    $connection_obj,
    "SELECT sol.id_solicitud, sol.estado, sol.fecha_solicitud, sol.fecha_requerida,
            s.titulo, uc.nombre AS cliente
     FROM solicitudes sol
     INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
     INNER JOIN usuarios uc ON uc.id_usuario = sol.id_cliente
     WHERE s.id_freelancer = $id_freelancer
     ORDER BY (sol.estado = 'pendiente') DESC, sol.fecha_solicitud DESC"
) or die(mysqli_error($connection_obj));

$solicitudes = mysqli_fetch_all($consulta, MYSQLI_ASSOC);

$pendientes = 0;
foreach ($solicitudes as $solicitud) {
    if ($solicitud['estado'] === 'pendiente') {
        $pendientes++;
    }
}

abrir_pagina('Solicitudes recibidas', $connection_obj);
?>

<div class="tarjeta">
    <h1>Solicitudes recibidas</h1>

    <?php if (isset($_GET['respondida'])): ?>
        <div class="alerta alerta-exito">La respuesta quedó guardada y el cliente fue notificado.</div>
    <?php endif; ?>

    <?php if (empty($solicitudes)): ?>
        <div class="alerta alerta-aviso">
            Todavía no has recibido solicitudes.
            <a href="<?= URL_BASE ?>/modulos/servicios/mis_servicios.php">Publica un servicio</a>
            para que los clientes puedan contratarte.
        </div>
    <?php else: ?>
        <p class="meta">
            <?= count($solicitudes) ?> solicitud<?= count($solicitudes) === 1 ? '' : 'es' ?>
            · <?= $pendientes ?> pendiente<?= $pendientes === 1 ? '' : 's' ?> de respuesta
        </p>

        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Servicio</th>
                        <th>Cliente</th>
                        <th>Fecha requerida</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <tr>
                            <td><?= (int) $solicitud['id_solicitud'] ?></td>
                            <td><?= htmlspecialchars($solicitud['titulo']) ?></td>
                            <td><?= htmlspecialchars($solicitud['cliente']) ?></td>
                            <td><?= fecha_corta($solicitud['fecha_requerida']) ?></td>
                            <td>
                                <span class="estado <?= clase_estado($solicitud['estado']) ?>">
                                    <?= htmlspecialchars(ucfirst($solicitud['estado'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($solicitud['estado'] === 'pendiente'): ?>
                                    <a href="responder.php?id=<?= (int) $solicitud['id_solicitud'] ?>">Responder</a>
                                <?php else: ?>
                                    <a href="detalle.php?id=<?= (int) $solicitud['id_solicitud'] ?>">Ver detalle</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php cerrar_pagina(); ?>
