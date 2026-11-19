<?php
// =====================================================================
// modulos/solicitudes/responder.php
// CU10 - Responder solicitud (RF11) y RF15 (guardar el estado)
//
// Flujo principal:
//   3. El freelancer la acepta o la rechaza.
//   4. El sistema cambia el estado de la solicitud.
//   5. El sistema avisa al cliente (CU13).
// Flujo alterno: "Si la solicitud ya no está pendiente, el sistema avisa
// y no deja responderla".
//
// Puntos importantes para la sustentación:
//   - La acción se hace con POST (formulario), no con enlaces. Cambiar
//     datos por GET es un error clásico porque el navegador puede repetir
//     la acción al recargar o al "precargar" enlaces.
//   - El UPDATE lleva la condición "AND estado = 'pendiente'". Si dos
//     pestañas intentan responder lo mismo, solo la primera afecta una
//     fila (rowCount() === 1) y la segunda recibe el aviso del flujo
//     alterno. Eso evita condiciones de carrera.
//   - El cambio de estado y el aviso al cliente van en la MISMA
//     transacción: o se guardan los dos, o no se guarda ninguno.
// =====================================================================

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_rol('freelancer');

$id_solicitud = (int) ($_GET['id'] ?? $_POST['id_solicitud'] ?? 0);

// La consulta filtra por s.id_freelancer = yo: si la solicitud no es de
// un servicio mío, no aparece y más abajo se responde 403.
$consulta = $pdo->prepare(
    'SELECT sol.id_solicitud, sol.descripcion, sol.estado, sol.fecha_solicitud,
            sol.fecha_requerida, sol.id_cliente,
            s.titulo, s.precio, uc.nombre AS cliente
     FROM solicitudes sol
     INNER JOIN servicios s  ON s.id_servicio = sol.id_servicio
     INNER JOIN usuarios  uc ON uc.id_usuario = sol.id_cliente
     WHERE sol.id_solicitud = :id AND s.id_freelancer = :freelancer'
);
$consulta->execute([
    ':id'         => $id_solicitud,
    ':freelancer' => $_SESSION['id_usuario'],
]);
$solicitud = $consulta->fetch();

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

        try {
            $pdo->beginTransaction();

            $actualizar = $pdo->prepare(
                "UPDATE solicitudes
                 SET estado = :estado
                 WHERE id_solicitud = :id AND estado = 'pendiente'"
            );
            $actualizar->execute([
                ':estado' => $nuevo_estado,
                ':id'     => $solicitud['id_solicitud'],
            ]);

            if ($actualizar->rowCount() !== 1) {
                // Alguien más la respondió entre que se abrió la página y
                // que se envió el formulario.
                $pdo->rollBack();
                $errores[] = 'La solicitud cambió de estado mientras la revisabas. Actualiza la página.';
            } else {
                // RF14 / CU13: aviso dentro de la plataforma
                crear_notificacion(
                    $pdo,
                    (int) $solicitud['id_cliente'],
                    (int) $solicitud['id_solicitud'],
                    'Tu solicitud #' . $solicitud['id_solicitud'] . ' para «' . $solicitud['titulo']
                        . '» fue ' . $nuevo_estado . '.'
                );

                $pdo->commit();
                header('Location: recibidas.php?respondida=1');
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al responder solicitud: ' . $e->getMessage());
            $errores[] = 'No se pudo guardar la respuesta. Inténtalo de nuevo.';
        }
    }
}

abrir_pagina('Responder solicitud', $pdo);
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
