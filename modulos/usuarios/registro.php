<?php

require_once __DIR__ . '/../../config/conexion.php';


$errores = [];
$registro_exitoso = false;
$nombre = '';
$correo = '';
$rol = 'cliente';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  
    $nombre     = trim($_POST['nombre'] ?? '');
    $correo     = mb_strtolower(trim($_POST['correo'] ?? ''));
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar  = $_POST['confirmar'] ?? '';
    $rol        = $_POST['rol'] ?? '';

  
    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
        $errores[] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150) {
        $errores[] = 'Escribe un correo electrónico válido.';
    }

    if (strlen($contrasena) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    if ($contrasena !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

  
    if (!in_array($rol, ['cliente', 'freelancer'], true)) {
        $errores[] = 'Elige un tipo de cuenta válido.';
    }

   
    $correo_seguro = mysqli_real_escape_string($connection_obj, $correo);

    if (empty($errores)) {
        $resultado = mysqli_query(
            $connection_obj,
            "SELECT COUNT(*) AS total FROM usuarios WHERE correo = '$correo_seguro'"
        ) or die(mysqli_error($connection_obj));

        $fila = mysqli_fetch_assoc($resultado);

        if ((int) $fila['total'] > 0) {
            $errores[] = 'Ese correo ya está registrado.';
        }
    }

    
    if (empty($errores)) {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $nombre_seguro = mysqli_real_escape_string($connection_obj, $nombre);
        $hash_seguro = mysqli_real_escape_string($connection_obj, $hash);
        $rol_seguro = mysqli_real_escape_string($connection_obj, $rol);

        mysqli_begin_transaction($connection_obj);

        $query = "INSERT INTO usuarios (nombre, correo, contrasena, rol)
                  VALUES ('$nombre_seguro', '$correo_seguro', '$hash_seguro', '$rol_seguro')";

        $insertado = mysqli_query($connection_obj, $query);

        if ($insertado && $rol === 'freelancer') {
            $id_nuevo = (int) mysqli_insert_id($connection_obj);

            $insertado = mysqli_query(
                $connection_obj,
                "INSERT INTO perfiles_freelancer (id_usuario) VALUES ($id_nuevo)"
            );
        }

        if ($insertado) {
            mysqli_commit($connection_obj);
            $registro_exitoso = true;

            $nombre = '';
            $correo = '';
            $rol = 'cliente';
        } else {
            mysqli_rollback($connection_obj);

            if (mysqli_errno($connection_obj) == 1062) {
                $errores[] = 'Ese correo ya está registrado.';
            } else {
                $errores[] = 'No se pudo completar el registro. Inténtalo de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta - Quick Employment</title>
    <link rel="stylesheet" href="/quick-employment/css/estilos.css">
</head>
<body>
    <div class="contenedor">
        <div class="tarjeta">
            <h1>Crear cuenta</h1>

            <?php if ($registro_exitoso): ?>
                <div class="alerta alerta-exito">
                    ¡Cuenta creada con éxito! Ya puedes
                    <a href="/quick-employment/modulos/usuarios/login.php">iniciar sesión</a>.
                </div>
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
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" required maxlength="100"
                       value="<?= htmlspecialchars($nombre) ?>">

                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" required maxlength="150"
                       value="<?= htmlspecialchars($correo) ?>">

                <label for="contrasena">Contraseña (mínimo 8 caracteres)</label>
                <input type="password" id="contrasena" name="contrasena" required minlength="8">

                <label for="confirmar">Confirmar contraseña</label>
                <input type="password" id="confirmar" name="confirmar" required minlength="8">

                <label>Tipo de cuenta</label>
                <label>
                    <input type="radio" name="rol" value="cliente" <?= $rol === 'cliente' ? 'checked' : '' ?>>
                    Cliente (busco servicios)
                </label>
                <label>
                    <input type="radio" name="rol" value="freelancer" <?= $rol === 'freelancer' ? 'checked' : '' ?>>
                    Freelancer (ofrezco servicios)
                </label>

                <button type="submit" class="boton">Crear cuenta</button>
            </form>
        </div>
    </div>
</body>
</html>
