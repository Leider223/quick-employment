<?php


require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_rol('freelancer');

$id_solicitud = (int) ($_GET['id'] ?? $_POST['id_solicitud'] ?? 0);

$id_freelancer = (int) $_SESSION['id_usuario'];

// La consulta filtra por s.id_freelancer = yo: si la solicitud no es de
// un servicio mío, no aparece y más abajo se responde 403.
$consulta = mysqli_query(
    $connection_obj,
    "SELECT sol.id_solicitud, sol.descripcion, sol.estado, sol.fecha_solicitud,
            sol.fecha_requerida, sol.id_cliente,
            s.titulo, s.precio, uc.nombre AS cliente
     FROM solicitudes sol
     INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
     INNER JOIN usuarios uc ON uc.id_usuario = sol.id_cliente
     WHERE sol.id_solicitud = $id_solicitud
       AND s.id_freelancer = $id_freelancer"
) or die(mysqli_error($connection_obj));

$solicitud = mysqli_fetch_assoc($consulta);

$errores = [];

if (!$solicitud) {
    http_response_code(404);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = post_texto('accion');

    if (!in_array($accion, ['aceptar', 'rechazar'], true)) {
        $errores[] = 'La respuesta enviada no es válida.';
    } elseif ($solicitud['estado'] !== 'pendiente') {
        // Flujo alterno del CU10
        $errores[] = 'Esta solicitud ya no está pendiente, así que no se puede responder.';
    } else {
        $nuevo_estado = $accion === 'aceptar' ? 'aceptada' : 'rechazada';

        $estadoSeguro = mysqli_real_escape_string($connection_obj, $nuevo_estado);
        $id_actual = (int) $solicitud['id_solicitud'];

        mysqli_begin_transaction($connection_obj);

        $actualizar = mysqli_query(
            $connection_obj,
            "UPDATE solicitudes
             SET estado = '$estadoSeguro'
             WHERE id_solicitud = $id_actual AND estado = 'pendiente'"
        );

        if ($actualizar && mysqli_affected_rows($connection_obj) === 1) {
            // RF14 / CU13: aviso dentro de la plataforma
            crear_notificacion(
                $connection_obj,
                (int) $solicitud['id_cliente'],
                $id_actual,
                'Tu solicitud #' . $id_actual . ' para «' . $solicitud['titulo']
                    . '» fue ' . $nuevo_estado . '.'
            );

            mysqli_commit($connection_obj);
            header('Location: recibidas.php?respondida=1');
            exit;
        }

        // Alguien más la respondió entre que se abrió la página y
        // que se envió el formulario.
        mysqli_rollback($connection_obj);
        $errores[] = 'La solicitud cambió de estado mientras la revisabas. Actualiza la página.';
    }
}

abrir_pagina('Responder solicitud', $connection_obj);
?>

<?php if (!$solicitud): ?>
    <div class="tarjeta">
        <h1>Responder solicitud</h1>
        <div class="alerta alerta-aviso">Esa solicitud no existe o no pertenece a tus servicios.</div>
        <a class="boton" href="recibidas.php">Volver a solicitudes recibidas</a>
    </div>
<?php else: ?>
    <div class="tarjeta">
        <h1>Solicitud #<?= (int) $solicitud['id_solicitud'] ?></h1>

        <table>
            <tr><th>Servicio</th><td><?= htmlspecialchars($solicitud['titulo']) ?></td></tr>
            <tr><th>Cliente</th><td><?= htmlspecialchars($solicitud['cliente']) ?></td></tr>
            <tr><th>Precio</th>
                <td><span class="precio">$<?= number_format($solicitud['precio'], 0, ',', '.') ?></span></td></tr>
            <tr><th>Enviada el</th><td><?= fecha_hora($solicitud['fecha_solicitud']) ?></td></tr>
            <tr><th>Fecha requerida</th><td><?= fecha_corta($solicitud['fecha_requerida']) ?></td></tr>
            <tr><th>Estado</th>
                <td>
                    <span class="estado <?= clase_estado($solicitud['estado']) ?>">
                        <?= htmlspecialchars(ucfirst($solicitud['estado'])) ?>
                    </span>
                </td>
            </tr>
        </table>

        <h2>Lo que necesita el cliente</h2>
        <p><?= nl2br(htmlspecialchars($solicitud['descripcion'])) ?></p>

        <?php if (!empty($errores)): ?>
            <div class="alerta alerta-error">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($solicitud['estado'] === 'pendiente'): ?>
            <form method="post" action="" class="acciones">
                <input type="hidden" name="id_solicitud" value="<?= (int) $solicitud['id_solicitud'] ?>">
                <button type="submit" name="accion" value="aceptar" class="boton">Aceptar</button>
                <button type="submit" name="accion" value="rechazar" class="boton boton-peligro">Rechazar</button>
            </form>
        <?php else: ?>
            <div class="alerta alerta-aviso">
                Esta solicitud ya fue respondida; no se puede cambiar de nuevo.
            </div>
        <?php endif; ?>

        <p><a href="recibidas.php">Volver a solicitudes recibidas</a></p>
    </div>
<?php endif; ?>

<?php cerrar_pagina(); ?>
