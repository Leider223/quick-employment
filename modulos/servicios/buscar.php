<?php
// =====================================================================
// modulos/servicios/buscar.php
// CU02 - Buscar servicios (RF03)
//
// Flujo principal del documento:
//   1. El cliente escribe una palabra clave o elige una categoria.
//   2. El sistema busca entre los servicios publicados.
//   3. El sistema muestra la lista de resultados.
// Flujo alterno: si no hay resultados, avisa y deja buscar otra cosa.
//
// Mejora adicional (no contradice el CU): filtros de precio minimo y
// maximo. El documento solo pide palabra clave y categoria.
//
// Decisiones tecnicas:
//   - La busqueda se arma con un arreglo de condiciones, asi el mismo
//     archivo sirve para buscar por texto, categoria, precio o una
//     combinacion, sin duplicar la consulta.
//   - Los valores NUNCA se pegan en el SQL: van como parametros (:) y
//     por eso no hay riesgo de inyeccion SQL.
//   - Se une perfiles_freelancer para saber si el freelancer esta
//     disponible y ocultar el boton Contratar cuando no lo esta.
// =====================================================================

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';

requerir_login();

$rol = $_SESSION['rol'] ?? '';

$texto = mb_substr(trim($_GET['q'] ?? ''), 0, 100);

$id_categoria = filter_input(
    INPUT_GET,
    'categoria',
    FILTER_VALIDATE_INT
);

$id_categoria = $id_categoria ?: 0;

// Obtener filtros de precio
$precio_min = isset($_GET['precio_min']) && $_GET['precio_min'] !== ''
    ? filter_var($_GET['precio_min'], FILTER_VALIDATE_FLOAT)
    : null;

$precio_max = isset($_GET['precio_max']) && $_GET['precio_max'] !== ''
    ? filter_var($_GET['precio_max'], FILTER_VALIDATE_FLOAT)
    : null;

// Validar precios
if ($precio_min !== null && ($precio_min === false || $precio_min < 0)) {
    $precio_min = null;
}

if ($precio_max !== null && ($precio_max === false || $precio_max < 0)) {
    $precio_max = null;
}

$categorias = $pdo->query(
    "SELECT id_categoria, nombre
    FROM categorias
    ORDER BY nombre"
)->fetchAll();

$condiciones = ["s.estado = :estado"];

$parametros = [
    ':estado' => 'activo'
];

if ($texto !== '') {
    $condiciones[] = "
        (
            s.titulo LIKE :texto_titulo
            OR s.descripcion LIKE :texto_descripcion
        )
    ";

    $parametros[':texto_titulo'] = '%' . $texto . '%';
    $parametros[':texto_descripcion'] = '%' . $texto . '%';
}

if ($id_categoria > 0) {
    $condiciones[] = 's.id_categoria = :categoria';
    $parametros[':categoria'] = $id_categoria;
}

if ($precio_min !== null) {
    $condiciones[] = 's.precio >= :precio_min';
    $parametros[':precio_min'] = $precio_min;
}
if ($precio_max !== null) {
    $condiciones[] = 's.precio <= :precio_max';
    $parametros[':precio_max'] = $precio_max;
}
$sql = "
    SELECT
        s.id_servicio,
        s.titulo,
        s.descripcion,
        s.precio,
        c.nombre AS categoria,
        u.id_usuario AS id_freelancer,
        u.nombre AS freelancer,
        COALESCE(p.disponible, 1) AS disponible
    FROM servicios s
    INNER JOIN usuarios u
        ON u.id_usuario = s.id_freelancer
    INNER JOIN categorias c
        ON c.id_categoria = s.id_categoria
    LEFT JOIN perfiles_freelancer p
        ON p.id_usuario = s.id_freelancer
    WHERE " . implode(' AND ', $condiciones) . "
    ORDER BY s.fecha_publicacion DESC
";

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);

$resultados = $consulta->fetchAll();

abrir_pagina('Buscar servicios', $pdo);
?>

<link rel="stylesheet" href="estilos.css">

<div class="pagina-servicios">

    <section class="tarjeta">

        <h1>Buscar servicios</h1>

        <p class="subtitulo-servicios">
            Encuentra profesionales y servicios que se adapten a tus necesidades.
        </p>

        <form method="GET" action="buscar.php" class="formulario-servicio">

            <label for="q">Palabra clave</label>

            <input
                type="text"
                id="q"
                name="q"
                maxlength="100"
                value="<?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Ejemplo: logo, página web, traducción"
            >

            <label for="categoria">Categoría</label>

            <select id="categoria" name="categoria">

                <option value="0">Todas las categorías</option>

                <?php foreach ($categorias as $categoria): ?>

                    <option
                        value="<?= (int) $categoria['id_categoria'] ?>"
                        <?= $id_categoria === (int) $categoria['id_categoria'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <label for="precio_min">Precio mínimo ($)</label>

            <input
                type="number"
                id="precio_min"
                name="precio_min"
                min="0"
                step="1"
                value="<?= htmlspecialchars($_GET['precio_min'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Ejemplo: 50000"
            >

            <label for="precio_max">Precio máximo ($)</label>

            <input
                type="number"
                id="precio_max"
                name="precio_max"
                min="0"
                step="1"
                value="<?= htmlspecialchars($_GET['precio_max'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Ejemplo: 200000"
            >

            <button type="submit">
                Buscar servicios
            </button>

            <a href="buscar.php" class="boton-cancelar">
                Limpiar búsqueda
            </a>

        </form>

    </section>

    <section class="seccion-resultados">

        <?php if (
            $precio_min !== null &&
            $precio_max !== null &&
            $precio_min > $precio_max
        ): ?>

            <div class="tarjeta mensaje-error">
                El precio mínimo no puede ser mayor que el precio máximo.
                Cambia los valores e intenta nuevamente.
            </div>

        <?php elseif (empty($resultados)): ?>

            <div class="tarjeta">

                <h2>Sin resultados</h2>

                <p>
                    No encontramos servicios con esos criterios.
                    Prueba con otra palabra, categoría o rango de precios.
                </p>

            </div>

        <?php else: ?>

            <h2>
                <?= count($resultados) ?>
                servicio<?= count($resultados) === 1 ? '' : 's' ?>
                encontrado<?= count($resultados) === 1 ? '' : 's' ?>
            </h2>

            <div class="contenedor-servicios">

                <?php foreach ($resultados as $servicio): ?>

                    <article class="tarjeta-servicio">

                        <h3>
                            <?= htmlspecialchars($servicio['titulo'], ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <p class="descripcion">
                            <?= nl2br(
                                htmlspecialchars(
                                    mb_substr($servicio['descripcion'], 0, 180),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ) ?>
                        </p>

                        <p class="categoria">
                            <strong>Freelancer:</strong>
                            <?= htmlspecialchars($servicio['freelancer'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p class="categoria">
                            <strong>Categoría:</strong>
                            <?= htmlspecialchars($servicio['categoria'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p class="precio">
                            $<?= number_format((float) $servicio['precio'], 0, ',', '.') ?>
                        </p>

                        <?php if (!(int) $servicio['disponible']): ?>

                            <span class="estado no-disponible">
                                No disponible
                            </span>

                        <?php else: ?>

                            <span class="estado activo">
                                Disponible
                            </span>

                        <?php endif; ?>

                        <div class="acciones-servicio">

                            <a
                                class="boton-editar"
                                href="../usuarios/perfil.php?id=<?= (int) $servicio['id_freelancer'] ?>"
                            >
                                Ver perfil
                            </a>

                            <?php if (
                                $rol === 'cliente' &&
                                (int) $servicio['disponible'] === 1
                            ): ?>

                                <a
                                    class="boton-contratar"
                                    href="../solicitudes/nueva_solicitud.php?id_servicio=<?= (int) $servicio['id_servicio'] ?>"
                                >
                                    Contratar
                                </a>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</div>

<?php cerrar_pagina(); ?>
