<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/sesion.php';

requerir_rol('freelancer');

$id_usuario = (int) $_SESSION['id_usuario'];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $presentacion = str_replace("\r\n", "\n", trim($_POST['presentacion'] ?? ''));
    $experiencia  = str_replace("\r\n", "\n", trim($_POST['experiencia'] ?? ''));
    // CU11: el freelancer puede marcarse como no disponible. La casilla solo
    // llega en el POST si está marcada, por eso se usa isset().
    $disponible   = isset($_POST['disponible']) ? 1 : 0;

    if (mb_strlen($presentacion) > 1000) {
        $errores[] = 'La presentación no puede superar los 1000 caracteres.';
    }

    if (mb_strlen($experiencia) > 2000) {
        $errores[] = 'La experiencia no puede superar los 2000 caracteres.';
    }

    if (empty($errores)) {
        $presentacionSegura = mysqli_real_escape_string($connection_obj, $presentacion);
        $experienciaSegura = mysqli_real_escape_string($connection_obj, $experiencia);
        $disponible = (int) $disponible;

        // Si el perfil ya existe lo actualiza; si no existe, lo crea.
        $query = "INSERT INTO perfiles_freelancer (id_usuario, presentacion, experiencia, disponible)
                  VALUES ($id_usuario, '$presentacionSegura', '$experienciaSegura', $disponible)
                  ON DUPLICATE KEY UPDATE
                      presentacion = VALUES(presentacion),
                      experiencia = VALUES(experiencia),
                      disponible = VALUES(disponible)";

        if (mysqli_query($connection_obj, $query)) {
            // Se recarga la página para mostrar lo que quedó guardado
            header('Location: editar_perfil.php?guardado=1');
            exit;
        }

        $errores[] = 'No se pudo guardar el perfil. Inténtalo de nuevo.';
    }
} else {

    $resultado = mysqli_query(
        $connection_obj,
        "SELECT presentacion, experiencia, disponible
         FROM perfiles_freelancer
         WHERE id_usuario = $id_usuario"
    ) or die(mysqli_error($connection_obj));

    $perfil = mysqli_fetch_assoc($resultado);

    $presentacion = $perfil['presentacion'] ?? '';
    $experiencia  = $perfil['experiencia'] ?? '';
    $disponible   = (int) ($perfil['disponible'] ?? 1);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mi perfil - Quick Employment</title>
    <link rel="stylesheet" href="/quick-employment/css/estilos.css">
</head>

<body>
    <div class="contenedor">
        <div class="tarjeta">
            <h1>Mi perfil</h1>
            <p>Sesión de <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong></p>

            <?php if (isset($_GET['guardado'])): ?>
                <div class="alerta alerta-exito">Perfil guardado correctamente.</div>
            <?php endif; ?>

            <?php if (!empty($errores)): ?>
                <div class="alerta alerta-error">
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <label for="presentacion">Presentación</label>
                <textarea id="presentacion" name="presentacion" rows="4" maxlength="1000"><?= htmlspecialchars($presentacion) ?></textarea>

                <label for="experiencia">Experiencia</label>
                <textarea id="experiencia" name="experiencia" rows="6" maxlength="2000"><?= htmlspecialchars($experiencia) ?></textarea>

                <label style="font-weight: normal; margin: 12px 0;">
                    <input type="checkbox" name="disponible" value="1" <?= $disponible ? 'checked' : '' ?>>
                    Estoy disponible para recibir solicitudes
                </label>

                <button type="submit" class="boton">Guardar cambios</button>
            </form>

            <p>
                <a href="/quick-employment/index.php">Volver</a> |
                <a href="/quick-employment/modulos/usuarios/logout.php">Cerrar sesión</a>
            </p>
        </div>
    </div>
</body>

</html>