<?php

// Inserta una notificacion para un usuario
function crear_notificacion($con, $id_usuario, $id_solicitud, $mensaje)
{
    // TEMPORAL: mientras terminamos de pasar todo a mysqli
    if ($con instanceof PDO) {
        $mensaje = mb_substr($mensaje, 0, 255);
        $insertar = $con->prepare(
            'INSERT INTO notificaciones (id_usuario, id_solicitud, mensaje)
             VALUES (:usuario, :solicitud, :mensaje)'
        );
        $insertar->execute([
            ':usuario'   => $id_usuario,
            ':solicitud' => $id_solicitud,
            ':mensaje'   => $mensaje,
        ]);
        return;
    }

    $id_usuario = (int) $id_usuario;
    $mensaje = mysqli_real_escape_string($con, mb_substr($mensaje, 0, 255));

    if ($id_solicitud === null) {
        $id_solicitud = "NULL";
    } else {
        $id_solicitud = (int) $id_solicitud;
    }

    $query = "INSERT INTO notificaciones (id_usuario, id_solicitud, mensaje)
              VALUES ($id_usuario, $id_solicitud, '$mensaje')";

    mysqli_query($con, $query) or die(mysqli_error($con));
}

// Cuenta las notificaciones que el usuario todavia no ha leido
function contar_no_leidas($con, $id_usuario)
{
    // TEMPORAL: mientras terminamos de pasar todo a mysqli
    if ($con instanceof PDO) {
        $consulta = $con->prepare(
            'SELECT COUNT(*) FROM notificaciones WHERE id_usuario = :id AND leida = 0'
        );
        $consulta->execute([':id' => $id_usuario]);
        return (int) $consulta->fetchColumn();
    }

    $id_usuario = (int) $id_usuario;

    $query = "SELECT COUNT(*) AS total
              FROM notificaciones
              WHERE id_usuario = $id_usuario AND leida = 0";

    $resultado = mysqli_query($con, $query) or die(mysqli_error($con));
    $fila = mysqli_fetch_assoc($resultado);

    return (int) $fila['total'];
}

// Marca como leidas todas las notificaciones del usuario
function marcar_notificaciones_leidas($con, $id_usuario)
{
    // TEMPORAL: mientras terminamos de pasar todo a mysqli
    if ($con instanceof PDO) {
        $actualizar = $con->prepare(
            'UPDATE notificaciones SET leida = 1 WHERE id_usuario = :id AND leida = 0'
        );
        $actualizar->execute([':id' => $id_usuario]);
        return;
    }

    $id_usuario = (int) $id_usuario;

    $query = "UPDATE notificaciones
              SET leida = 1
              WHERE id_usuario = $id_usuario AND leida = 0";

    mysqli_query($con, $query) or die(mysqli_error($con));
}
