<?php


require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_login();

$id_solicitud = (int) ($_GET['id'] ?? 0);

$consulta = mysqli_query(
    $connection_obj,
    "SELECT sol.id_solicitud, sol.descripcion, sol.fecha_solicitud, sol.fecha_requerida,
            sol.estado, sol.confirma_cliente, sol.confirma_freelancer,
            sol.id_cliente,
            s.id_servicio, s.titulo, s.precio, s.descripcion AS servicio_descripcion,
            s.id_freelancer,
            uc.nombre AS cliente, uf.nombre AS freelancer,
            p.id_pago, p.valor, p.metodo, p.fecha_pago,
            cal.puntuacion, cal.comentario
     FROM solicitudes sol
     INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
     INNER JOIN usuarios uc ON uc.id_usuario = sol.id_cliente
     INNER JOIN usuarios uf ON uf.id_usuario = s.id_freelancer
     LEFT JOIN pagos p ON p.id_solicitud = sol.id_solicitud
     LEFT JOIN calificaciones cal ON cal.id_solicitud = sol.id_solicitud
     WHERE sol.id_solicitud = $id_solicitud"
) or die(mysqli_error($connection_obj));

$solicitud = mysqli_fetch_assoc($consulta);


$autorizado = false;
if ($solicitud) {
    $yo = (int) $_SESSION['id_usuario'];
    $autorizado = $_SESSION['rol'] === 'cliente'
        ? (int) $solicitud['id_cliente'] === $yo
        : (int) $solicitud['id_freelancer'] === $yo;
}

if ($solicitud && !$autorizado) {
    http_response_code(403);
    abrir_pagina('Solicitud no disponible', $connection_obj);
    ?>
    <div class="tarjeta">
        <div class="alerta alerta-error">No tienes permiso para ver esta solicitud.</div>
        <a class="boton" href="<?= URL_BASE ?>/index.php">Volver al inicio</a>
    </div>
    <?php
    cerrar_pagina();
    exit;
}

abrir_pagina('Detalle de la solicitud', $connection_obj);
?>

<?php if (!$solicitud): ?>
    <div class="tarjeta">
        <h1>Detalle de la solicitud</h1>
        <div class="alerta alerta-aviso">Esa solicitud no existe.</div>
        <a class="boton" href="<?= URL_BASE ?>/index.php">Volver al inicio</a>
    </div>
<?php else: ?>
    <div class="tarjeta">
        <h1>Solicitud #<?= (int) $solicitud['id_solicitud'] ?></h1>
        <p>
            <span class="estado <?= clase_estado($solicitud['estado']) ?>">
                <?= htmlspecialchars(ucfirst($solicitud['estado'])) ?>
            </span>
        </p>

        <table>
            <tr><th>Servicio</th><td><?= htmlspecialchars($solicitud['titulo']) ?></td></tr>
            <tr><th>Precio</th>
                <td><span class="precio">$<?= number_format($solicitud['precio'], 0, ',', '.') ?></span></td></tr>
            <tr><th>Cliente</th><td><?= htmlspecialchars($solicitud['cliente']) ?></td></tr>
            <tr><th>Freelancer</th><td><?= htmlspecialchars($solicitud['freelancer']) ?></td></tr>
            <tr><th>Enviada el</th><td><?= fecha_hora($solicitud['fecha_solicitud']) ?></td></tr>
            <tr><th>Fecha requerida</th><td><?= fecha_corta($solicitud['fecha_requerida']) ?></td></tr>
        </table>

        <h2>Lo que necesita el cliente</h2>
        <p><?= nl2br(htmlspecialchars($solicitud['descripcion'])) ?></p>

        <h2>Descripción del servicio</h2>
        <p><?= nl2br(htmlspecialchars($solicitud['servicio_descripcion'])) ?></p>
    </div>

    <?php if (isset($_GET['pago_registrado'])): ?>
        <div class="alerta alerta-exito">El pago externo quedó registrado. La solicitud todavía no está finalizada.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error_cancelacion'])): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($_GET['error_cancelacion']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['calificada'])): ?>
        <div class="alerta alerta-exito">Tu calificación quedó guardada.</div>
    <?php endif; ?>
    <?php if (isset($_GET['confirmacion'])): ?>
        <div class="alerta alerta-exito">Tu confirmación quedó registrada. La solicitud se finalizará cuando ambas partes confirmen.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error_confirmacion'])): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($_GET['error_confirmacion']) ?></div>
    <?php endif; ?>

    <div class="tarjeta">
        <h2>Pago</h2>
        <?php if ($solicitud['fecha_pago'] === null): ?>
            <p>Todavía no hay pago registrado.</p>
        <?php else: ?>
            <p>
                $<?= number_format($solicitud['valor'], 0, ',', '.') ?>
                · <?= htmlspecialchars($solicitud['metodo']) ?>
                · <?= fecha_corta($solicitud['fecha_pago']) ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="tarjeta">
        <h2>Calificación del cliente</h2>
        <?php if ($solicitud['puntuacion'] === null): ?>
            <p>Todavía no está calificada.</p>
        <?php else: ?>
            <p><strong><?= (int) $solicitud['puntuacion'] ?> / 5</strong></p>
            <?php if ($solicitud['comentario'] !== null && $solicitud['comentario'] !== ''): ?>
                <p><?= nl2br(htmlspecialchars($solicitud['comentario'])) ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($_SESSION['rol'] === 'cliente' &&
        in_array($solicitud['estado'], ['pendiente', 'aceptada'], true) &&
        $solicitud['fecha_pago'] === null &&
        (int) $solicitud['confirma_cliente'] === 0 &&
        (int) $solicitud['confirma_freelancer'] === 0): ?>
        <div class="tarjeta">
            <h2>Cancelar solicitud</h2>
            <form method="post" action="cancelar.php" onsubmit="return confirm('¿Seguro que deseas cancelar esta solicitud?');">
                <input type="hidden" name="id_solicitud" value="<?= (int) $solicitud['id_solicitud'] ?>">
                <button class="boton boton-peligro" type="submit">Cancelar solicitud</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="tarjeta">
        <h2>Finalización del servicio</h2>
        <p>Para finalizar el servicio, el cliente y el freelancer deben confirmar que el trabajo fue completado.</p>
        <p>
            <strong>Cliente:</strong> <?= (int) $solicitud['confirma_cliente'] === 1 ? 'Confirmado' : 'Pendiente' ?><br>
            <strong>Freelancer:</strong> <?= (int) $solicitud['confirma_freelancer'] === 1 ? 'Confirmado' : 'Pendiente' ?>
        </p>

        <?php if ($solicitud['estado'] === 'aceptada' && $solicitud['fecha_pago'] === null && $_SESSION['rol'] === 'cliente'): ?>
            <a class="boton" href="../pagos_calificaciones/pagar.php?id=<?= (int) $solicitud['id_solicitud'] ?>">Registrar pago externo</a>
        <?php endif; ?>

        <?php
            $mi_confirmacion = $_SESSION['rol'] === 'cliente'
                ? (int) $solicitud['confirma_cliente']
                : (int) $solicitud['confirma_freelancer'];
        ?>
        <?php if ($solicitud['estado'] === 'aceptada' && $solicitud['fecha_pago'] !== null && !$mi_confirmacion): ?>
            <form method="post" action="confirmar_finalizacion.php">
                <input type="hidden" name="id_solicitud" value="<?= (int) $solicitud['id_solicitud'] ?>">
                <button class="boton" type="submit">Confirmar que el servicio terminó</button>
            </form>
        <?php elseif ($solicitud['estado'] === 'aceptada' && $mi_confirmacion): ?>
            <div class="alerta alerta-aviso">Ya confirmaste. Estamos esperando la confirmación de la otra parte.</div>
        <?php elseif ($solicitud['estado'] === 'finalizada'): ?>
            <div class="alerta alerta-exito">Servicio finalizado por confirmación de ambas partes.</div>
        <?php endif; ?>

        <?php if ($_SESSION['rol'] === 'cliente' && $solicitud['estado'] === 'finalizada' && $solicitud['puntuacion'] === null): ?>
            <a class="boton" href="../pagos_calificaciones/calificar.php?id=<?= (int) $solicitud['id_solicitud'] ?>">Calificar servicio</a>
        <?php endif; ?>
    </div>

    <?php if ($_SESSION['rol'] === 'cliente'): ?>
        <p><a class="boton" href="mis_solicitudes.php">Volver a mis solicitudes</a></p>
    <?php else: ?>
        <p><a class="boton" href="recibidas.php">Volver a solicitudes recibidas</a></p>
    <?php endif; ?>
<?php endif; ?>

<?php cerrar_pagina(); ?>
