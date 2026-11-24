<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/funciones_perfil.php';

$id = (int) ($_GET['id'] ?? 0);

$perfil      = obtener_perfil_freelancer($connection_obj, $id);
$habilidades = obtener_habilidades_freelancer($connection_obj, $id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de funciones - Quick Employment</title>
    <link rel="stylesheet" href="/quick-employment/css/estilos.css">
</head>
<body>
    <div class="contenedor">
        <div class="tarjeta">
            <?php if ($perfil === null): ?>
                <div class="alerta alerta-aviso">No existe ese freelancer.</div>
            <?php else: ?>
                <h1><?= htmlspecialchars($perfil['nombre']) ?></h1>
                <p><strong>Disponible:</strong> <?= $perfil['disponible'] ? 'Sí' : 'No' ?></p>

                <h2>Presentación</h2>
                <p><?= nl2br(htmlspecialchars($perfil['presentacion'] ?? '')) ?></p>

                <h2>Experiencia</h2>
                <p><?= nl2br(htmlspecialchars($perfil['experiencia'] ?? '')) ?></p>

                <h2>Habilidades</h2>
                <?php if (empty($habilidades)): ?>
                    <p>Todavía no ha agregado habilidades.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($habilidades as $habilidad): ?>
                            <li><?= htmlspecialchars($habilidad['nombre']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>