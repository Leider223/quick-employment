<?php

// Inserta una notificacion para un usuario
function crear_notificacion($con, $id_usuario, $id_solicitud, $mensaje)
{
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
    $id_usuario = (int) $id_usuario;

    $query = "UPDATE notificaciones
              SET leida = 1
              WHERE id_usuario = $id_usuario AND leida = 0";

    mysqli_query($con, $query) or die(mysqli_error($con));
}
