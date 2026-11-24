<?php

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';
require_once __DIR__ . '/../../includes/notificaciones.php';

requerir_rol('cliente');

$idCliente = (int) $_SESSION['id_usuario'];
$id = (int) ($_POST['id_solicitud'] ?? $_GET['id'] ?? 0);
$error = '';
$metodos = ['Tarjeta', 'Transferencia', 'Efectivo', 'PSE'];
$solicitud = null;

if ($id > 0) {
    $consulta = mysqli_query(
        $connection_obj,
        "SELECT sol.id_solicitud, sol.estado, s.titulo, s.precio, s.id_freelancer,
                u.nombre AS freelancer, p.id_pago, p.metodo, p.fecha_pago
         FROM solicitudes sol
         INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
         INNER JOIN usuarios u ON u.id_usuario = s.id_freelancer
         LEFT JOIN pagos p ON p.id_solicitud = sol.id_solicitud
         WHERE sol.id_solicitud = $id AND sol.id_cliente = $idCliente"
    ) or die(mysqli_error($connection_obj));

    $solicitud = mysqli_fetch_assoc($consulta);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodo = $_POST['metodo'] ?? '';

    if (!$solicitud) {
        $error = 'La solicitud no existe o no es tuya.';
    } elseif (!in_array($metodo, $metodos, true)) {
        $error = 'Selecciona un método de pago.';
    } elseif ($solicitud['estado'] !== 'aceptada') {
        $error = 'Solo se puede pagar una solicitud aceptada.';
    } elseif ($solicitud['id_pago'] !== null) {
        $error = 'Esta solicitud ya tiene un pago registrado.';
    } else {
        mysqli_begin_transaction($connection_obj);

        $consulta = mysqli_query(
            $connection_obj,
            "SELECT sol.estado, s.precio, s.titulo, s.id_freelancer
             FROM solicitudes sol
             INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
             WHERE sol.id_solicitud = $id AND sol.id_cliente = $idCliente
             FOR UPDATE"
        );

        $sol = mysqli_fetch_assoc($consulta);

        $consultaPago = mysqli_query(
            $connection_obj,
            "SELECT id_pago FROM pagos WHERE id_solicitud = $id"
        );
        $ya_pago = mysqli_num_rows($consultaPago) > 0;

        if (!$sol) {
            mysqli_rollback($connection_obj);
            $error = 'La solicitud no existe o no es tuya.';
        } elseif ($sol['estado'] !== 'aceptada') {
            mysqli_rollback($connection_obj);
            $error = 'La solicitud cambió de estado.';
        } elseif ($ya_pago) {
            mysqli_rollback($connection_obj);
            $error = 'Esta solicitud ya tiene un pago registrado.';
        } else {
            $metodoSeguro = mysqli_real_escape_string($connection_obj, $metodo);
            $precio = (float) $sol['precio'];

            $insertar = mysqli_query(
                $connection_obj,
                "INSERT INTO pagos (id_solicitud, valor, metodo, fecha_pago)
                 VALUES ($id, $precio, '$metodoSeguro', CURDATE())"
            );

            if ($insertar) {
                crear_notificacion(
                    $connection_obj,
                    (int) $sol['id_freelancer'],
                    $id,
                    'El cliente registró el pago de $'
                    . number_format((float) $sol['precio'], 0, ',', '.')
                    . " por '" . $sol['titulo'] . "'. Método: " . $metodo . '.'
                );

                mysqli_commit($connection_obj);
                header('Location: ../solicitudes/detalle.php?id=' . $id . '&pago_registrado=1');
                exit;
            }

            mysqli_rollback($connection_obj);
            $error = 'No se pudo registrar el pago.';
        }
    }
}

$por_pagar = [];

if ($id === 0) {
    $consulta = mysqli_query(
        $connection_obj,
        "SELECT sol.id_solicitud, s.titulo, s.precio, u.nombre AS freelancer
         FROM solicitudes sol
         INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
         INNER JOIN usuarios u ON u.id_usuario = s.id_freelancer
         LEFT JOIN pagos p ON p.id_solicitud = sol.id_solicitud
         WHERE sol.id_cliente = $idCliente
           AND sol.estado = 'aceptada'
           AND p.id_pago IS NULL
         ORDER BY sol.fecha_solicitud DESC"
    ) or die(mysqli_error($connection_obj));

    $por_pagar = mysqli_fetch_all($consulta, MYSQLI_ASSOC);
}

abrir_pagina('Pagar servicio', $connection_obj);
?>

<div class="tarjeta">
    <h1>Pagar servicio</h1>

    <?php if ($error): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($id === 0): ?>
        <p>Elige una solicitud aceptada para registrar el pago.</p>

        <?php if (empty($por_pagar)): ?>
            <div class="alerta alerta-aviso">
                No tienes solicitudes aceptadas pendientes de pago.
                <a href="../solicitudes/mis_solicitudes.php">Ver mis solicitudes</a>.
            </div>
        <?php else: ?>
            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Servicio</th>
                            <th>Freelancer</th>
                            <th>Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($por_pagar as $fila): ?>
                            <tr>
                                <td><?= (int) $fila['id_solicitud'] ?></td>
                                <td><?= htmlspecialchars($fila['titulo']) ?></td>
                                <td><?= htmlspecialchars($fila['freelancer']) ?></td>
                                <td>$<?= number_format((float) $fila['precio'], 0, ',', '.') ?></td>
                                <td>
                                    <a class="boton" href="pagar.php?id=<?= (int) $fila['id_solicitud'] ?>">Pagar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php elseif (!$solicitud): ?>
        <div class="alerta alerta-error">Esa solicitud no existe o no es tuya.</div>
        <a class="boton" href="pagar.php">Volver</a>
    <?php elseif ($solicitud['id_pago'] !== null): ?>
        <div class="alerta alerta-exito">
            Esta solicitud ya está pagada con
            <?= htmlspecialchars($solicitud['metodo']) ?>
            el <?= fecha_corta($solicitud['fecha_pago']) ?>.
        </div>
        <a class="boton" href="../solicitudes/detalle.php?id=<?= (int) $solicitud['id_solicitud'] ?>">Ver detalle</a>
    <?php elseif ($solicitud['estado'] !== 'aceptada'): ?>
        <div class="alerta alerta-aviso">
            Solo se puede pagar una solicitud aceptada. Esta está
            <?= htmlspecialchars(ucfirst($solicitud['estado'])) ?>.
        </div>
        <a class="boton" href="../solicitudes/detalle.php?id=<?= (int) $solicitud['id_solicitud'] ?>">Ver detalle</a>
    <?php else: ?>
        <p>Servicio: <strong><?= htmlspecialchars($solicitud['titulo']) ?></strong></p>
        <p>Freelancer: <?= htmlspecialchars($solicitud['freelancer']) ?></p>
        <p>Valor: <span class="precio">$<?= number_format((float) $solicitud['precio'], 0, ',', '.') ?></span></p>

        <form method="post">
            <input type="hidden" name="id_solicitud" value="<?= (int) $solicitud['id_solicitud'] ?>">

            <label for="metodo">Método de pago</label>
            <select name="metodo" id="metodo" required>
                <option value="">Selecciona un método</option>
                <?php foreach ($metodos as $metodo): ?>
                    <option value="<?= htmlspecialchars($metodo) ?>"><?= htmlspecialchars($metodo) ?></option>
                <?php endforeach; ?>
            </select>

            <button class="boton" type="submit">Confirmar pago</button>
            <a class="boton boton-secundario" href="../solicitudes/detalle.php?id=<?= (int) $solicitud['id_solicitud'] ?>">Cancelar</a>
        </form>

        <p class="meta">Pago simulado: no se conecta a una pasarela real.</p>
    <?php endif; ?>
</div>

<?php cerrar_pagina(); ?>
