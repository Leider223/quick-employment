<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/sesion.php';

if (usuario_logueado()) {
    header('Location: ' . URL_BASE . '/index.php');
    exit;
}

$correo = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo     = mb_strtolower(trim($_POST['correo'] ?? ''));
    $contrasena = $_POST['contrasena'] ?? '';

    if ($correo === '' || $contrasena === '') {
        $error = 'Escribe tu correo y tu contraseña.';
    } else {
        $consulta = $pdo->prepare(
            'SELECT id_usuario, nombre, contrasena, rol FROM usuarios WHERE correo = :correo'
        );
        $consulta->execute([':correo' => $correo]);
        $usuario = $consulta->fetch();

        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            iniciar_sesion_usuario($usuario);
            header('Location: ' . URL_BASE . '/index.php');
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesion</title>
    <link rel="stylesheet" href="/quick-employment/css/estilos.css">
</head>

<body>
    <div class="contenedor">
        <div class="tarjeta">
            <h1>Iniciar sesión</h1>

            <?php if (isset($_GET['salio'])): ?>
                <div class="alerta alerta-exito">Cerraste sesión correctamente.</div>
            <?php endif; ?>
            
            <?php if ($error !== ''): ?>
                <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <label for="correo">Correo:</label>
                <input type="email" name="correo" id="correo" required value="<?= htmlspecialchars($correo) ?>">

                <label for="contrasena">Contraseña:</label>
                <input type="password" name="contrasena" id="contrasena" required>

                <button type="submit" class="boton">Entrar</button>


            </form>
            <p>¿No tienes cuenta?
                <a href="/quick-employment/modulos/usuarios/registro.php">Crear cuenta</a>
            </p>
        </div>
    </div>
</body>

</html>