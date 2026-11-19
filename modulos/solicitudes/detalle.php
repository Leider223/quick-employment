<?php
// =====================================================================
// modulos/solicitudes/detalle.php
// Detalle de una solicitud (paso 3 del CU05)
//
// Lo pueden ver los dos actores del negocio, pero cada uno solo las
// suyas:
//   - el CLIENTE ve las solicitudes que él mismo envió;
//   - el FREELANCER ve las solicitudes que recibió en sus servicios.
// Si un usuario intenta abrir una solicitud ajena, se responde 403.
//
// También muestra el pago y la calificación si existen. Eso deja lista
// la pantalla para los casos de uso CU06 (pagar) y CU07 (calificar),
// que todavía no están programados.
// =====================================================================

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_login();

$id_solicitud = (int) ($_GET['id'] ?? 0);

$consulta = $pdo->prepare(
    'SELECT sol.id_solicitud, sol.descripcion, sol.fecha_solicitud, sol.fecha_requerida,
            sol.estado, sol.confirma_cliente, sol.confirma_freelancer,
            sol.id_cliente,
            s.id_servicio, s.titulo, s.precio, s.descripcion AS servicio_descripcion,
            s.id_freelancer,
            uc.nombre AS cliente, uf.nombre AS freelancer,
            p.valor, p.metodo, p.fecha_pago,
            cal.puntuacion, cal.comentario
     FROM solicitudes sol
     INNER JOIN servicios s  ON s.id_servicio  = sol.id_servicio
     INNER JOIN usuarios  uc ON uc.id_usuario  = sol.id_cliente
     INNER JOIN usuarios  uf ON uf.id_usuario  = s.id_freelancer
     LEFT  JOIN pagos p      ON p.id_solicitud = sol.id_solicitud
     LEFT  JOIN calificaciones cal ON cal.id_solicitud = sol.id_solicitud
     WHERE sol.id_solicitud = :id'
);
$consulta->execute([':id' => $id_solicitud]);
$solicitud = $consulta->fetch();

// Sin fila -> no existe. Con fila -> solo la ve el cliente dueño o el
// freelancer del servicio.
$autorizado = false;
if ($solicitud) {
    $yo = (int) $_SESSION['id_usuario'];
    $autorizado = $_SESSION['rol'] === 'cliente'
        ? (int) $solicitud['id_cliente'] === $yo
        : (int) $solicitud['id_freelancer'] === $yo;
}

if ($solicitud && !$autorizado) {
    http_response_code(403);
    abrir_pagina('Solicitud no disponible', $pdo);
    ?>
    <div class="tarjeta">
        <div class="alerta alerta-error">No tienes permiso para ver esta solicitud.</div>
        <a class="boton" href="<?= URL_BASE ?>/index.php">Volver al inicio</a>
    </div>
    <?php
    cerrar_pagina();
    exit;
}

abrir_pagina('Detalle de la solicitud', $pdo);
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

    <?php if ($_SESSION['rol'] === 'cliente'): ?>
        <p><a class="boton" href="mis_solicitudes.php">Volver a mis solicitudes</a></p>
    <?php else: ?>
        <p><a class="boton" href="recibidas.php">Volver a solicitudes recibidas</a></p>
    <?php endif; ?>
<?php endif; ?>

<?php cerrar_pagina(); ?>
