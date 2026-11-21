<?php
// =====================================================================
// modulos/servicios/mis_servicios.php
// CU09 - Publicar y editar servicios (RF10)
//
// Flujo principal del documento:
//   1. El freelancer pulsa "Nuevo servicio".
//   2. Escribe titulo, descripcion, categoria y precio.
//   3. El sistema revisa los datos y publica el servicio.
//   4. Despues puede editar o quitar el servicio.
// Flujo alterno: si faltan datos, se piden y no se guarda nada.
//
// Decisiones tecnicas:
//   - requerir_rol('freelancer'): el actor del CU es el freelancer; si
//     entra un cliente recibe 403 y no ve la pantalla.
//   - El dueno del servicio NUNCA viene del formulario: sale de la
//     sesion ($_SESSION['id_usuario']). Asi nadie publica a nombre de otro.
//   - Editar y retirar llevan "AND id_freelancer = ?" para que un
//     freelancer no pueda tocar los servicios de otro.
//   - Los valores van como parametros (?) y no pegados al SQL.
// =====================================================================

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_rol('freelancer');

$idFreelancer = (int) $_SESSION['id_usuario'];
$mensaje      = '';
$error        = '';
$servicioEditar = null;

// Categorias para el <select> del formulario
$categorias = $pdo->query(
    "SELECT id_categoria, nombre
    FROM categorias
    ORDER BY nombre"
)->fetchAll();

// ---------------------------------------------------------------------
// Formularios (siempre por POST: crear, editar y retirar cambian datos)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ---- Retirar un servicio propio --------------------------------
    if ($accion === 'retirar') {
        $idServicio = filter_input(
            INPUT_POST,
            'id_servicio',
            FILTER_VALIDATE_INT
        );

        if (!$idServicio) {
            $error = 'El servicio seleccionado no es valido.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE servicios
                    SET estado = 'retirado'
                    WHERE id_servicio = ?
                      AND id_freelancer = ?
                      AND estado = 'activo'"
                );
                $stmt->execute([$idServicio, $idFreelancer]);

                if ($stmt->rowCount() === 1) {
                    header('Location: mis_servicios.php?retirado=1');
                    exit;
                }

                $error = 'El servicio no existe, ya esta retirado o no es tuyo.';
            } catch (PDOException $e) {
                error_log('Error al retirar servicio: ' . $e->getMessage());
                $error = 'No se pudo retirar el servicio.';
            }
        }
    }

    // ---- Publicar o actualizar un servicio -------------------------
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
            try {
                $verificarCategoria = $pdo->prepare(
                    "SELECT id_categoria
                    FROM categorias
                    WHERE id_categoria = ?"
                );
                $verificarCategoria->execute([$idCategoria]);

                if (!$verificarCategoria->fetch()) {
                    $error = 'La categoria seleccionada no es valida.';
                } elseif ($accion === 'registrar') {
                    // Un servicio nuevo siempre nace activo y a nombre
                    // del freelancer que tiene la sesion.
                    $stmt = $pdo->prepare(
                        "INSERT INTO servicios
                            (id_freelancer, id_categoria, titulo,
                             descripcion, precio, estado)
                        VALUES (?, ?, ?, ?, ?, 'activo')"
                    );
                    $stmt->execute([
                        $idFreelancer,
                        $idCategoria,
                        $titulo,
                        $descripcion,
                        $precio
                    ]);

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
                        // Primero se confirma que el servicio sea de este
                        // freelancer; si no, no se actualiza nada.
                        $propio = $pdo->prepare(
                            "SELECT id_servicio
                            FROM servicios
                            WHERE id_servicio = ? AND id_freelancer = ?"
                        );
                        $propio->execute([$idServicio, $idFreelancer]);

                        if (!$propio->fetch()) {
                            $error = 'El servicio no existe o no es tuyo.';
                        } else {
                            $stmt = $pdo->prepare(
                                "UPDATE servicios
                                SET id_categoria = ?,
                                    titulo = ?,
                                    descripcion = ?,
                                    precio = ?,
                                    estado = ?
                                WHERE id_servicio = ? AND id_freelancer = ?"
                            );
                            $stmt->execute([
                                $idCategoria,
                                $titulo,
                                $descripcion,
                                $precio,
                                $estado,
                                $idServicio,
                                $idFreelancer
                            ]);

                            header('Location: mis_servicios.php?actualizado=1');
                            exit;
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log('Error al guardar servicio: ' . $e->getMessage());
                $error = 'No se pudo guardar el servicio.';
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
        $stmtEditar = $pdo->prepare(
            "SELECT *
            FROM servicios
            WHERE id_servicio = ? AND id_freelancer = ?"
        );
        $stmtEditar->execute([$idEditar, $idFreelancer]);
        $servicioEditar = $stmtEditar->fetch();

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
$stmt = $pdo->prepare(
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
    WHERE s.id_freelancer = ?
    ORDER BY s.estado ASC, s.id_servicio DESC"
);
$stmt->execute([$idFreelancer]);
$servicios = $stmt->fetchAll();

abrir_pagina('Mis servicios', $pdo);
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
