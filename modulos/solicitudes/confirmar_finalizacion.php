<?php

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';
require_once __DIR__ . '/../../includes/notificaciones.php';

requerir_login();

$id = (int) ($_POST['id_solicitud'] ?? $_GET['id'] ?? 0);
$yo = (int) $_SESSION['id_usuario'];
$rol = $_SESSION['rol'];
$error = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($connection_obj);

    $consulta = mysqli_query(
        $connection_obj,
        "SELECT sol.id_solicitud, sol.estado, sol.id_cliente,
                sol.confirma_cliente, sol.confirma_freelancer,
                s.id_freelancer, s.titulo, p.id_pago
         FROM solicitudes sol
         INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
         LEFT JOIN pagos p ON p.id_solicitud = sol.id_solicitud
         WHERE sol.id_solicitud = $id
         FOR UPDATE"
    );

    $sol = mysqli_fetch_assoc($consulta);

    if (!$sol) {
        mysqli_rollback($connection_obj);
        $error = 'La solicitud no existe.';
    } elseif (
        ($rol === 'cliente' && (int) $sol['id_cliente'] !== $yo) ||
        ($rol === 'freelancer' && (int) $sol['id_freelancer'] !== $yo)
    ) {
        mysqli_rollback($connection_obj);
        http_response_code(403);
        $error = 'No tienes permiso para confirmar esta solicitud.';
    } elseif ($sol['estado'] !== 'aceptada') {
        mysqli_rollback($connection_obj);
        $error = 'Solo se puede confirmar la finalización de una solicitud aceptada.';
    } elseif ($sol['id_pago'] === null) {
        mysqli_rollback($connection_obj);
        $error = 'Primero debe registrarse el pago externo para esta solicitud.';
    } else {
        $campo = $rol === 'cliente' ? 'confirma_cliente' : 'confirma_freelancer';

        mysqli_query(
            $connection_obj,
            "UPDATE solicitudes SET $campo = 1
             WHERE id_solicitud = $id AND estado = 'aceptada'"
        );

        $consultaEstado = mysqli_query(
            $connection_obj,
            "SELECT confirma_cliente, confirma_freelancer
             FROM solicitudes
             WHERE id_solicitud = $id"
        );

        $confirmaciones = mysqli_fetch_assoc($consultaEstado);

        if ((int) $confirmaciones['confirma_cliente'] === 1 &&
            (int) $confirmaciones['confirma_freelancer'] === 1) {
            mysqli_query(
                $connection_obj,
                "UPDATE solicitudes SET estado = 'finalizada'
                 WHERE id_solicitud = $id AND estado = 'aceptada'
                   AND confirma_cliente = 1 AND confirma_freelancer = 1"
            );

            crear_notificacion(
                $connection_obj,
                $rol === 'cliente' ? (int) $sol['id_freelancer'] : (int) $sol['id_cliente'],
                $id,
                'Ambas partes confirmaron la finalización del servicio «' . $sol['titulo'] . '». Ya se puede calificar.'
            );
            $mensaje = 'Ambas partes confirmaron. La solicitud quedó finalizada.';
        } else {
            crear_notificacion(
                $connection_obj,
                $rol === 'cliente' ? (int) $sol['id_freelancer'] : (int) $sol['id_cliente'],
                $id,
                ($rol === 'cliente' ? 'El cliente' : 'El freelancer')
                . ' confirmó la finalización del servicio «' . $sol['titulo']
                . '». Falta la confirmación de la otra parte.'
            );
            $mensaje = 'Tu confirmación quedó registrada. La solicitud se finalizará cuando la otra parte también confirme.';
        }

        mysqli_commit($connection_obj);
        header('Location: detalle.php?id=' . $id . '&confirmacion=1');
        exit;
    }
}

header('Location: detalle.php?id=' . $id . ($error !== '' ? '&error_confirmacion=' . urlencode($error) : ''));
exit;
