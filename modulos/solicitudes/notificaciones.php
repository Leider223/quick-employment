<?php

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_login();

$id_usuario = (int) $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    marcar_notificaciones_leidas($connection_obj, $id_usuario);
    header('Location: notificaciones.php?leidas=1');
    exit;
}

$consulta = mysqli_query(
    $connection_obj,
    "SELECT id_notificacion, id_solicitud, mensaje, fecha, leida
     FROM notificaciones
     WHERE id_usuario = $id_usuario
     ORDER BY fecha DESC
     LIMIT 50"
) or die(mysqli_error($connection_obj));

$avisos = mysqli_fetch_all($consulta, MYSQLI_ASSOC);

$no_leidas = contar_no_leidas($connection_obj, $id_usuario);

abrir_pagina('Notificaciones', $connection_obj);
?>

<div class="tarjeta">
    <h1>Notificaciones</h1>

    <?php if (isset($_GET['leidas'])): ?>
        <div class="alerta alerta-exito">Todas las notificaciones quedaron marcadas como leídas.</div>
    <?php endif; ?>

    <?php if (empty($avisos)): ?>
        <div class="alerta alerta-aviso">No tienes notificaciones.</div>
    <?php else: ?>
        <p class="meta">
            <?= count($avisos) ?> aviso<?= count($avisos) === 1 ? '' : 's' ?>
            · <?= $no_leidas ?> sin leer
        </p>

        <ul class="avisos">
            <?php foreach ($avisos as $aviso): ?>
                <li class="aviso <?= (int) $aviso['leida'] === 0 ? 'aviso-nueva' : '' ?>">
                    <p>
                        <?= htmlspecialchars($aviso['mensaje']) ?>
                        <?php if ((int) $aviso['leida'] === 0): ?>
                            <span class="badge-nueva">nueva</span>
                        <?php endif; ?>
                    </p>
                    <p class="meta"><?= fecha_hora($aviso['fecha']) ?></p>

                    <?php if ($aviso['id_solicitud'] !== null): ?>
                        <a href="detalle.php?id=<?= (int) $aviso['id_solicitud'] ?>">Ver la solicitud</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($no_leidas > 0): ?>
            <form method="post" action="" class="acciones">
                <button type="submit" class="boton boton-secundario">Marcar todas como leídas</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php cerrar_pagina(); ?>
