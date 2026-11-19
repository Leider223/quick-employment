<?php
require_once __DIR__ . '/includes/sesion.php';

if (isset($_GET['salir'])) {
    cerrar_sesion_usuario();
    header('Location: probar_sesion.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Prueba de sesión - Quick Employment</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>
    <div class="contenedor">
        <div class="tarjeta">
            <h1>Prueba de sesión</h1>

            <?php if (usuario_logueado()): ?>
                <div class="alerta alerta-exito">
                    Sesión iniciada como <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>
                    (rol: <?= htmlspecialchars($_SESSION['rol']) ?>, id: <?= (int) $_SESSION['id_usuario'] ?>)
                </div>

                <?php if ($_SESSION['rol'] === 'freelancer'): ?>
                    <a class="boton" href="modulos/usuarios/editar_perfil.php">Editar mi perfil</a>
                    <a class="boton" href="modulos/usuarios/editar_habilidades.php">Mis habilidades</a>
                <?php endif; ?>

                <a class="boton boton-peligro" href="?salir=1">Cerrar sesión</a>

            <?php else: ?>
                <div class="alerta alerta-aviso">No hay sesión iniciada.</div>
                <a class="boton" href="modulos/usuarios/login.php">Ir a iniciar sesión</a>
            <?php endif; ?>
            
        </div>
    </div>
</body>

</html>