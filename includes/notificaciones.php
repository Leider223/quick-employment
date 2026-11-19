<?php
// =====================================================================
// includes/notificaciones.php
// Funciones del caso de uso CU13 (Notificar) y del RF14.
//
// El documento dice: "El sistema envía un aviso cuando hay una solicitud
// nueva, una respuesta o un pago". En este proyecto el aviso NO se manda
// por correo: se guarda en la tabla `notificaciones` y el usuario lo ve
// dentro de la plataforma. Eso cubre el flujo alterno del CU13 ("si falla
// el envío, el aviso queda pendiente y se ve dentro de la plataforma").
//
// Se llaman SIEMPRE dentro de la misma transacción de la acción que las
// origina (enviar solicitud, aceptar, rechazar, pagar). Así, si algo
// falla, no queda ni la solicitud ni el aviso a medias.
// =====================================================================

// Crea un aviso para un usuario. Lanza la excepción si falla, para que
// quien la llamó pueda deshacer la transacción completa.
function crear_notificacion(PDO $pdo, int $id_usuario, ?int $id_solicitud, string $mensaje): void
{
    // La columna es VARCHAR(255): se recorta para no provocar un error.
    $mensaje = mb_substr($mensaje, 0, 255);

    $insertar = $pdo->prepare(
        'INSERT INTO notificaciones (id_usuario, id_solicitud, mensaje)
         VALUES (:usuario, :solicitud, :mensaje)'
    );
    $insertar->execute([
        ':usuario'   => $id_usuario,
        ':solicitud' => $id_solicitud,
        ':mensaje'   => $mensaje,
    ]);
}

// Cuenta los avisos sin leer. La usa el menú para mostrar "Notificaciones (2)".
function contar_no_leidas(PDO $pdo, int $id_usuario): int
{
    $consulta = $pdo->prepare(
        'SELECT COUNT(*) FROM notificaciones WHERE id_usuario = :id AND leida = 0'
    );
    $consulta->execute([':id' => $id_usuario]);

    return (int) $consulta->fetchColumn();
}

// Marca todos los avisos del usuario como leídos.
function marcar_notificaciones_leidas(PDO $pdo, int $id_usuario): void
{
    $actualizar = $pdo->prepare(
        'UPDATE notificaciones SET leida = 1 WHERE id_usuario = :id AND leida = 0'
    );
    $actualizar->execute([':id' => $id_usuario]);
}
