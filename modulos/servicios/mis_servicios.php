<?php



require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_rol('freelancer');

$idFreelancer = (int) $_SESSION['id_usuario'];
$mensaje      = '';
$error        = '';
$servicioEditar = null;

// Categorias para el <select> del formulario
$resultadoCategorias = mysqli_query(
    $connection_obj,
    "SELECT id_categoria, nombre
    FROM categorias
    ORDER BY nombre"
) or die(mysqli_error($connection_obj));

$categorias = mysqli_fetch_all($resultadoCategorias, MYSQLI_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    
    if ($accion === 'retirar') {
        $idServicio = filter_input(
            INPUT_POST,
            'id_servicio',
            FILTER_VALIDATE_INT
        );

        if (!$idServicio) {
            $error = 'El servicio seleccionado no es valido.';
        } else {
            $idServicio = (int) $idServicio;

            $query = "UPDATE servicios
                      SET estado = 'retirado'
                      WHERE id_servicio = $idServicio
                        AND id_freelancer = $idFreelancer
                        AND estado = 'activo'";

            $resultado = mysqli_query($connection_obj, $query);

            if ($resultado && mysqli_affected_rows($connection_obj) === 1) {
                header('Location: mis_servicios.php?retirado=1');
                exit;
            }

            $error = 'El servicio no existe, ya esta retirado o no es tuyo.';
        }
    }

    
    elseif (in_array($accion, ['registrar', 'actualizar'], true)) {
        $idCategoria = filter_input(
            INPUT_POST,
            'id_categoria',
            FILTER_VALIDATE_INT
        );

        $titulo      = post_texto('titulo');
        $descripcion = post_texto('descripcion');
        $precio      = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
        $estado      = $_POST['estado'] ?? 'activo';

        if (
            !$idCategoria ||
            $titulo === '' ||
            $descripcion === '' ||
            $precio === false ||
            $precio === null ||
            $precio <= 0
        ) {
            $error = 'Completa titulo, descripcion, categoria y un precio mayor que cero.';
        } elseif (mb_strlen($titulo) > 150) {
            $error = 'El titulo no puede superar los 150 caracteres.';
        } elseif ($precio > 99999999.99) {
            $error = 'El precio es demasiado alto.';
        } elseif (!in_array($estado, ['activo', 'retirado'], true)) {
            $error = 'El estado seleccionado no es valido.';
        } else {
            $idCategoria = (int) $idCategoria;
            $tituloSeguro = mysqli_real_escape_string($connection_obj, $titulo);
            $descripcionSegura = mysqli_real_escape_string($connection_obj, $descripcion);
            $precio = (float) $precio;

            $verificarCategoria = mysqli_query(
                $connection_obj,
                "SELECT id_categoria
                 FROM categorias
                 WHERE id_categoria = $idCategoria"
            );

            if (mysqli_num_rows($verificarCategoria) === 0) {
                $error = 'La categoria seleccionada no es valida.';
            } elseif ($accion === 'registrar') {
                // Un servicio nuevo siempre nace activo y a nombre
                // del freelancer que tiene la sesion.
                $query = "INSERT INTO servicios
                            (id_freelancer, id_categoria, titulo,
                             descripcion, precio, estado)
                          VALUES ($idFreelancer, $idCategoria, '$tituloSeguro',
                                  '$descripcionSegura', $precio, 'activo')";

                mysqli_query($connection_obj, $query) or die(mysqli_error($connection_obj));

                header('Location: mis_servicios.php?registrado=1');
                exit;
            } else {
                $idServicio = filter_input(
                    INPUT_POST,
                    'id_servicio',
                    FILTER_VALIDATE_INT
                );

                if (!$idServicio) {
                    $error = 'El servicio seleccionado no es valido.';
                } else {
                    $idServicio = (int) $idServicio;

                    // Primero se confirma que el servicio sea de este
                    // freelancer; si no, no se actualiza nada.
                    $propio = mysqli_query(
                        $connection_obj,
                        "SELECT id_servicio
                         FROM servicios
                         WHERE id_servicio = $idServicio
                           AND id_freelancer = $idFreelancer"
                    );

                    if (mysqli_num_rows($propio) === 0) {
                        $error = 'El servicio no existe o no es tuyo.';
                    } else {
                        $estadoSeguro = mysqli_real_escape_string($connection_obj, $estado);

                        $query = "UPDATE servicios
                                  SET id_categoria = $idCategoria,
                                      titulo = '$tituloSeguro',
                                      descripcion = '$descripcionSegura',
                                      precio = $precio,
                                      estado = '$estadoSeguro'
                                  WHERE id_servicio = $idServicio
                                    AND id_freelancer = $idFreelancer";

                        mysqli_query($connection_obj, $query) or die(mysqli_error($connection_obj));

                        header('Location: mis_servicios.php?actualizado=1');
                        exit;
                    }
                }
            }
        }
    } else {
        $error = 'La accion solicitada no es valida.';
    }
}

// ---------------------------------------------------------------------
// Mensajes despues de redirigir (patron POST - Redirect - GET)
// ---------------------------------------------------------------------
if (isset($_GET['registrado'])) {
    $mensaje = 'Servicio publicado correctamente.';
} elseif (isset($_GET['actualizado'])) {
    $mensaje = 'Servicio actualizado correctamente.';
} elseif (isset($_GET['retirado'])) {
    $mensaje = 'Servicio retirado. Ya no aparece en las busquedas.';
}

// ---------------------------------------------------------------------
// Servicio que se va a editar (solo si es del freelancer de la sesion)
// ---------------------------------------------------------------------
if (isset($_GET['editar'])) {
    $idEditar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);

    if ($idEditar) {
        $idEditar = (int) $idEditar;

        $resultadoEditar = mysqli_query(
            $connection_obj,
            "SELECT *
             FROM servicios
             WHERE id_servicio = $idEditar
               AND id_freelancer = $idFreelancer"
        );

        $servicioEditar = mysqli_fetch_assoc($resultadoEditar);

        if (!$servicioEditar) {
            $error = 'El servicio que intentas editar no existe o no es tuyo.';
        }
    } else {
        $error = 'El identificador del servicio no es valido.';
    }
}

// ---------------------------------------------------------------------
// Listado: solo los servicios del freelancer logueado
// ---------------------------------------------------------------------
$resultadoServicios = mysqli_query(
    $connection_obj,
    "SELECT
        s.id_servicio,
        s.titulo,
        s.descripcion,
        s.precio,
        s.estado,
        s.fecha_publicacion,
        c.nombre AS categoria
    FROM servicios s
    INNER JOIN categorias c
        ON c.id_categoria = s.id_categoria
    WHERE s.id_freelancer = $idFreelancer
    ORDER BY s.estado ASC, s.id_servicio DESC"
) or die(mysqli_error($connection_obj));

$servicios = mysqli_fetch_all($resultadoServicios, MYSQLI_ASSOC);

abrir_pagina('Mis servicios', $connection_obj);
?>

<link rel="stylesheet" href="estilos.css">

<div class="pagina-servicios">
    <h1>Mis servicios</h1>
    <p class="subtitulo-servicios">
        Publica lo que ofreces y administra tus servicios.
    </p>

    <?php if ($mensaje): ?>
        <p class="mensaje-exito">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="mensaje-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <p class="acciones-servicio">
        <a href="#formulario-servicio" class="boton-editar">
            Nuevo servicio
        </a>
    </p>

    <section id="formulario-servicio">
        <h2>
            <?= $servicioEditar ? 'Editar servicio' : 'Publicar nuevo servicio' ?>
        </h2>

        <form method="POST" action="mis_servicios.php" class="formulario-servicio">
            <?php if ($servicioEditar): ?>
                <input
                    type="hidden"
                    name="id_servicio"
                    value="<?= (int) $servicioEditar['id_servicio'] ?>"
                >
            <?php endif; ?>

            <label for="id_categoria">Categoria:</label>
            <select name="id_categoria" id="id_categoria" required>
                <option value="">Selecciona una categoria</option>

                <?php foreach ($categorias as $categoria): ?>
                    <option
                        value="<?= (int) $categoria['id_categoria'] ?>"
                        <?= $servicioEditar &&
                            (int) $servicioEditar['id_categoria'] === (int) $categoria['id_categoria']
                            ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="titulo">Titulo del servicio:</label>
            <input
                type="text"
                name="titulo"
                id="titulo"
                maxlength="150"
                required
                value="<?= htmlspecialchars($servicioEditar['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="descripcion">Descripcion:</label>
            <textarea
                name="descripcion"
                id="descripcion"
                rows="4"
                required
            ><?= htmlspecialchars($servicioEditar['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <label for="precio">Precio:</label>
            <input
                type="number"
                name="precio"
                id="precio"
                min="1"
                step="0.01"
                required
                value="<?= htmlspecialchars((string) ($servicioEditar['precio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            >

            <?php if ($servicioEditar): ?>
                <label for="estado">Estado:</label>
                <select name="estado" id="estado" required>
                    <option
                        value="activo"
                        <?= ($servicioEditar['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>
                    >
                        Activo (visible en las busquedas)
                    </option>
                    <option
                        value="retirado"
                        <?= ($servicioEditar['estado'] ?? '') === 'retirado' ? 'selected' : '' ?>
                    >
                        Retirado
                    </option>
                </select>
            <?php endif; ?>

            <?php if ($servicioEditar): ?>
                <button type="submit" name="accion" value="actualizar">
                    Guardar cambios
                </button>
                <a href="mis_servicios.php" class="boton-cancelar">
                    Cancelar edicion
                </a>
            <?php else: ?>
                <button type="submit" name="accion" value="registrar">
                    Publicar servicio
                </button>
            <?php endif; ?>
        </form>
    </section>

    <section>
        <h2>Servicios publicados</h2>

        <div class="contenedor-servicios">
            <?php if (empty($servicios)): ?>
                <p>Todavia no has publicado servicios.</p>
            <?php else: ?>
                <?php foreach ($servicios as $servicio): ?>
                    <article class="tarjeta-servicio">
                        <h3>
                            <?= htmlspecialchars($servicio['titulo'], ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <p class="descripcion">
                            <?= nl2br(htmlspecialchars($servicio['descripcion'], ENT_QUOTES, 'UTF-8')) ?>
                        </p>

                        <p class="categoria">
                            <strong>Categoria:</strong>
                            <?= htmlspecialchars($servicio['categoria'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p class="precio">
                            $<?= number_format((float) $servicio['precio'], 0, ',', '.') ?>
                        </p>

                        <p>
                            <span class="estado <?= $servicio['estado'] === 'activo' ? 'activo' : 'retirado' ?>">
                                <?= htmlspecialchars(ucfirst($servicio['estado']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </p>

                        <p class="categoria">
                            Publicado el <?= htmlspecialchars(fecha_corta($servicio['fecha_publicacion']), ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <div class="acciones-servicio">
                            <a
                                href="mis_servicios.php?editar=<?= (int) $servicio['id_servicio'] ?>"
                                class="boton-editar"
                            >
                                Editar
                            </a>

                            <?php if ($servicio['estado'] === 'activo'): ?>
                                <form
                                    method="POST"
                                    action="mis_servicios.php"
                                    onsubmit="return confirm('¿Seguro que quieres retirar este servicio?');"
                                >
                                    <input type="hidden" name="accion" value="retirar">
                                    <input
                                        type="hidden"
                                        name="id_servicio"
                                        value="<?= (int) $servicio['id_servicio'] ?>"
                                    >
                                    <button type="submit" class="boton-retirar">
                                        Retirar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php cerrar_pagina(); ?>
