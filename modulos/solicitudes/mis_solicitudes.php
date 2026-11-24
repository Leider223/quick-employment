<?php


require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_rol('cliente');

$id_cliente = (int) $_SESSION['id_usuario'];

$consulta = mysqli_query(
    $connection_obj,
    "SELECT sol.id_solicitud, sol.estado, sol.fecha_solicitud, sol.fecha_requerida,
            s.titulo, u.nombre AS freelancer
     FROM solicitudes sol
     INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
     INNER JOIN usuarios u ON u.id_usuario = s.id_freelancer
     WHERE sol.id_cliente = $id_cliente
     ORDER BY sol.fecha_solicitud DESC"
) or die(mysqli_error($connection_obj));

$solicitudes = mysqli_fetch_all($consulta, MYSQLI_ASSOC);

abrir_pagina('Mis solicitudes', $connection_obj);
?>

<div class="tarjeta">
    <h1>Mis solicitudes</h1>

    <?php if (isset($_GET['enviada'])): ?>
        <div class="alerta alerta-exito">Solicitud enviada. El freelancer la revisará.</div>
    <?php endif; ?>

    <?php if (empty($solicitudes)): ?>
        <div class="alerta alerta-aviso">
            Todavía no has enviado solicitudes.
            <a href="../servicios/buscar.php">Busca un servicio</a> para empezar.
        </div>
    <?php else: ?>
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Servicio</th>
                        <th>Freelancer</th>
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
                            <td><?= htmlspecialchars($solicitud['freelancer']) ?></td>
                            <td><?= fecha_corta($solicitud['fecha_requerida']) ?></td>
                            <td>
                                <span class="estado <?= clase_estado($solicitud['estado']) ?>">
                                    <?= htmlspecialchars(ucfirst($solicitud['estado'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="detalle.php?id=<?= (int) $solicitud['id_solicitud'] ?>">Ver detalle</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php cerrar_pagina(); ?>
