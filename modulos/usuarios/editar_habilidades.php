<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/sesion.php';

requerir_rol('freelancer');

$id_usuario = (int) $_SESSION['id_usuario'];
$mensaje = '';
$tipo_mensaje = '';

$resultadoLista = mysqli_query(
    $connection_obj,
    'SELECT id_habilidad, nombre FROM habilidades ORDER BY nombre'
) or die(mysqli_error($connection_obj));

$lista = mysqli_fetch_all($resultadoLista, MYSQLI_ASSOC);
$ids_validos = array_map('intval', array_column($lista, 'id_habilidad'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $enviadas = $_POST['habilidades'] ?? [];
    if (!is_array($enviadas)) {
        $enviadas = [];
    }

    $seleccionadas = [];
    $hay_invalida  = false;

    foreach ($enviadas as $id) {
        // Solo se aceptan valores simples; un envío manipulado (por ejemplo un
        // arreglo anidado o un texto) cuenta como habilidad inválida.
        if (!is_int($id) && !is_string($id)) {
            $hay_invalida = true;
            break;
        }

        $id = (int) $id;

        if (!in_array($id, $ids_validos, true)) {
            $hay_invalida = true;
            break;
        }

        if (!in_array($id, $seleccionadas, true)) {
            $seleccionadas[] = $id;
        }
    }

    // CU08, flujo alterno: si alguna habilidad no es válida, se muestra el
    // error y no se guarda nada (la selección anterior se conserva).
    if ($hay_invalida) {
        $mensaje = 'Alguna de las habilidades enviadas no es válida. No se guardó la selección.';
        $tipo_mensaje = 'error';
    } else {
        mysqli_begin_transaction($connection_obj);

        $ok = mysqli_query(
            $connection_obj,
            "DELETE FROM freelancer_habilidades WHERE id_freelancer = $id_usuario"
        );

        if ($ok) {
            foreach ($seleccionadas as $id_habilidad) {
                $ok = mysqli_query(
                    $connection_obj,
                    "INSERT INTO freelancer_habilidades (id_freelancer, id_habilidad)
                     VALUES ($id_usuario, $id_habilidad)"
                );

                if (!$ok) {
                    break;
                }
            }
        }

        if ($ok) {
            mysqli_commit($connection_obj);
            $mensaje = 'Tus habilidades se guardaron correctamente.';
            $tipo_mensaje = 'exito';
        } else {
            mysqli_rollback($connection_obj);
            $mensaje = 'No se pudieron guardar las habilidades. Inténtalo de nuevo.';
            $tipo_mensaje = 'error';
        }
    }
}


$resultadoMias = mysqli_query(
    $connection_obj,
    "SELECT id_habilidad FROM freelancer_habilidades WHERE id_freelancer = $id_usuario"
) or die(mysqli_error($connection_obj));

$mis_habilidades = [];
while ($fila = mysqli_fetch_assoc($resultadoMias)) {
    $mis_habilidades[] = (int) $fila['id_habilidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis habilidades - Quick Employment</title>
    <link rel="stylesheet" href="/quick-employment/css/estilos.css">
</head>
<body>
    <div class="contenedor">
        <div class="tarjeta">
            <h1>Mis habilidades</h1>
            <p>Marca las habilidades que tienes.</p>

            <?php if ($mensaje !== ''): ?>
                <div class="alerta alerta-<?= $tipo_mensaje === 'exito' ? 'exito' : 'error' ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php foreach ($lista as $habilidad): ?>
                    <label style="font-weight: normal; margin: 6px 0;">
                        <input type="checkbox" name="habilidades[]"
                            value="<?= (int) $habilidad['id_habilidad'] ?>"
                            <?= in_array((int) $habilidad['id_habilidad'], $mis_habilidades, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($habilidad['nombre']) ?>
                    </label>
                <?php endforeach; ?>

                <button type="submit" class="boton">Guardar habilidades</button>
            </form>

            <p>
                <a href="/quick-employment/modulos/usuarios/editar_perfil.php">Mi perfil</a> |
                <a href="/quick-employment/index.php">Volver</a>
            </p>
        </div>
    </div>
</body>
</html>