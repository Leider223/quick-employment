<?php

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_rol('cliente');

$idCliente = (int) $_SESSION['id_usuario'];
$id = (int) ($_POST['id_solicitud'] ?? $_GET['id'] ?? 0);
$error = '';
$solicitud = null;

$consulta = mysqli_query(
    $connection_obj,
    "SELECT sol.id_solicitud, sol.estado, s.titulo, s.id_freelancer,
            p.id_pago, cal.id_calificacion
     FROM solicitudes sol
     INNER JOIN servicios s ON s.id_servicio = sol.id_servicio
     LEFT JOIN pagos p ON p.id_solicitud = sol.id_solicitud
     LEFT JOIN calificaciones cal ON cal.id_solicitud = sol.id_solicitud
     WHERE sol.id_solicitud = $id AND sol.id_cliente = $idCliente"
) or die(mysqli_error($connection_obj));

$solicitud = mysqli_fetch_assoc($consulta);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $puntuacion = filter_input(INPUT_POST, 'puntuacion', FILTER_VALIDATE_INT);
    $comentario = post_texto('comentario');

    if (!$solicitud) {
        $error = 'La solicitud no existe o no te pertenece.';
    } elseif ($solicitud['estado'] !== 'finalizada' || $solicitud['id_pago'] === null) {
        $error = 'Solo puedes calificar un servicio finalizado y con el pago registrado.';
    } elseif ($solicitud['id_calificacion'] !== null) {
        $error = 'Este servicio ya fue calificado.';
    } elseif ($puntuacion === false || $puntuacion < 1 || $puntuacion > 5) {
        $error = 'Selecciona una puntuación entre 1 y 5.';
    } elseif (mb_strlen($comentario) > 2000) {
        $error = 'El comentario no puede superar los 2000 caracteres.';
    } else {
        if ($comentario !== '') {
            $comentarioSeguro = "'" . mysqli_real_escape_string($connection_obj, $comentario) . "'";
        } else {
            $comentarioSeguro = 'NULL';
        }

        $insertar = mysqli_query(
            $connection_obj,
            "INSERT INTO calificaciones (id_solicitud, puntuacion, comentario)
             VALUES ($id, $puntuacion, $comentarioSeguro)"
        );

        if ($insertar) {
            header('Location: ../solicitudes/detalle.php?id=' . $id . '&calificada=1');
            exit;
        }

        $error = 'No se pudo guardar la calificación. Es posible que ya exista.';
    }
}

abrir_pagina('Calificar servicio', $connection_obj);
?>
<div class="tarjeta">
    <h1>Calificar servicio</h1>
    <?php if ($error): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$solicitud): ?>
        <div class="alerta alerta-aviso">La solicitud no existe o no te pertenece.</div>
    <?php elseif ($solicitud['id_calificacion'] !== null): ?>
        <div class="alerta alerta-aviso">Este servicio ya fue calificado.</div>
        <a class="boton" href="../solicitudes/detalle.php?id=<?= $id ?>">Volver al detalle</a>
    <?php elseif ($solicitud['estado'] !== 'finalizada' || $solicitud['id_pago'] === null): ?>
        <div class="alerta alerta-aviso">Podrás calificar cuando el servicio esté finalizado por ambas partes y tenga el pago registrado.</div>
    <?php else: ?>
        <p>Servicio: <strong><?= htmlspecialchars($solicitud['titulo']) ?></strong></p>
        <form method="post">
            <input type="hidden" name="id_solicitud" value="<?= $id ?>">
            <label for="puntuacion">Puntuación</label>
            <select name="puntuacion" id="puntuacion" required>
                <option value="">Selecciona una puntuación</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>"><?= $i ?> / 5</option>
                <?php endfor; ?>
            </select>
            <label for="comentario">Comentario (opcional)</label>
            <textarea name="comentario" id="comentario" rows="4" maxlength="2000"></textarea>
            <button class="boton" type="submit">Guardar calificación</button>
        </form>
    <?php endif; ?>
</div>
<?php cerrar_pagina(); ?>
