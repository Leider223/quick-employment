<?php

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';
require_once __DIR__ . '/../../includes/notificaciones.php';

requerir_rol('cliente');

$id = (int) ($_POST['id_solicitud'] ?? 0);
$yo = (int) $_SESSION['id_usuario'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
    header('Location: mis_solicitudes.php');
    exit;
}

mysqli_begin_transaction($connection_obj);

$consulta = mysqli_query(
    $connection_obj,
    "SELECT sol.estado, sol.confirma_cliente, sol.confirma_freelancer,
            s.id_freelancer, s.titulo, p.id_pago
     FROM solicitudes sol
     INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
     LEFT JOIN pagos p ON p.id_solicitud = sol.id_solicitud
     WHERE sol.id_solicitud = $id AND sol.id_cliente = $yo
     FOR UPDATE"
);

$sol = mysqli_fetch_assoc($consulta);

if (!$sol) {
    mysqli_rollback($connection_obj);
    $error = 'La solicitud no existe o no te pertenece.';
} elseif (!in_array($sol['estado'], ['pendiente', 'aceptada'], true)) {
    mysqli_rollback($connection_obj);
    $error = 'Esta solicitud ya no se puede cancelar.';
} elseif (
    $sol['id_pago'] !== null ||
    (int) $sol['confirma_cliente'] === 1 ||
    (int) $sol['confirma_freelancer'] === 1
) {
    mysqli_rollback($connection_obj);
    $error = 'No se puede cancelar después de registrar el pago o iniciar la confirmación de finalización.';
} else {
    $actualizar = mysqli_query(
        $connection_obj,
        "UPDATE solicitudes SET estado = 'cancelada'
         WHERE id_solicitud = $id AND estado IN ('pendiente','aceptada')"
    );

    if ($actualizar && mysqli_affected_rows($connection_obj) === 1) {
        crear_notificacion(
            $connection_obj,
            (int) $sol['id_freelancer'],
            $id,
            'El cliente canceló la solicitud para el servicio «' . $sol['titulo'] . '».'
        );

        mysqli_commit($connection_obj);
        header('Location: mis_solicitudes.php?cancelada=1');
        exit;
    }

    mysqli_rollback($connection_obj);
    $error = 'La solicitud cambió de estado. Inténtalo de nuevo.';
}

header('Location: detalle.php?id=' . $id . '&error_cancelacion=' . urlencode($error));
exit;
