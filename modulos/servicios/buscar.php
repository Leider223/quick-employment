<?php
// =====================================================================
// modulos/servicios/buscar.php
// CU02 - Buscar servicios (RF03)
//
// Flujo principal del documento:
//   1. El cliente escribe lo que busca o elige una categoría.
//   2. El sistema busca entre los servicios publicados.
//   3. El sistema muestra la lista de resultados.
// Flujo alterno: si no hay resultados, avisa y deja buscar otra cosa.
//
// Decisiones técnicas:
//   - La búsqueda se arma con un arreglo de condiciones, así el mismo
//     archivo sirve para buscar solo por texto, solo por categoría o por
//     las dos cosas, sin duplicar la consulta.
//   - Los valores NUNCA se pegan en el SQL: van como parámetros (:texto,
//     :categoria), por eso no hay riesgo de inyección SQL.
//   - Se unen `categorias` y `perfiles_freelancer` para mostrar el nombre
//     de la categoría y si el freelancer está disponible.
// =====================================================================

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';

requerir_login();

$rol           = $_SESSION['rol'];
$texto         = mb_substr(trim($_GET['q'] ?? ''), 0, 100);
$id_categoria  = (int) ($_GET['categoria'] ?? 0);

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre')->fetchAll();

$condiciones = ["s.estado = :estado"];
$parametros  = [':estado' => 'activo'];

if ($texto !== '') {
    // Se usan dos parámetros distintos porque con consultas preparadas
    // reales (EMULATE_PREPARES = false) un mismo nombre no se puede repetir.
    $condiciones[] = '(s.titulo LIKE :texto_titulo OR s.descripcion LIKE :texto_descripcion)';
    $parametros[':texto_titulo']       = '%' . $texto . '%';
    $parametros[':texto_descripcion']  = '%' . $texto . '%';
}

if ($id_categoria > 0) {
    $condiciones[] = 's.id_categoria = :categoria';
    $parametros[':categoria'] = $id_categoria;
}

$sql = 'SELECT s.id_servicio, s.titulo, s.descripcion, s.precio,
               c.nombre AS categoria,
               u.id_usuario AS id_freelancer, u.nombre AS freelancer,
               COALESCE(p.disponible, 1) AS disponible
        FROM servicios s
        INNER JOIN usuarios u          ON u.id_usuario = s.id_freelancer
        INNER JOIN categorias c        ON c.id_categoria = s.id_categoria
        LEFT  JOIN perfiles_freelancer p ON p.id_usuario = s.id_freelancer
        WHERE ' . implode(' AND ', $condiciones) . '
        ORDER BY s.fecha_publicacion DESC';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);
$resultados = $consulta->fetchAll();

abrir_pagina('Buscar servicios', $pdo);
?>

<div class="tarjeta">
    <h1>Buscar servicios</h1>

    <form method="get" action="">
        <label for="q">Palabra clave</label>
        <input type="text" id="q" name="q" maxlength="100"
               value="<?= htmlspecialchars($texto) ?>"
               placeholder="Por ejemplo: logo, página web, traducción">

        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
            <option value="0">Todas las categorías</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= (int) $categoria['id_categoria'] ?>"
                    <?= $id_categoria === (int) $categoria['id_categoria'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($categoria['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="boton">Buscar</button>
    </form>
</div>

<?php if (empty($resultados)): ?>
    <div class="tarjeta">
        <div class="alerta alerta-aviso">
            No se encontraron servicios con esa búsqueda. Prueba con otra palabra o categoría.
        </div>
    </div>
<?php else: ?>
    <div class="tarjeta">
        <h2><?= count($resultados) ?> servicio<?= count($resultados) === 1 ? '' : 's' ?> encontrado<?= count($resultados) === 1 ? '' : 's' ?></h2>

        <?php foreach ($resultados as $servicio): ?>
            <div class="resultado">
                <h2><?= htmlspecialchars($servicio['titulo']) ?></h2>
                <p class="meta">
                    <?= htmlspecialchars($servicio['freelancer']) ?> ·
                    <?= htmlspecialchars($servicio['categoria']) ?> ·
                    <span class="precio">$<?= number_format($servicio['precio'], 0, ',', '.') ?></span>
                </p>
                <p><?= nl2br(htmlspecialchars(mb_substr($servicio['descripcion'], 0, 180))) ?></p>

                <?php if (!(int) $servicio['disponible']): ?>
                    <span class="estado estado-cancelada">No disponible</span>
                <?php endif; ?>

                <p>
                    <a class="boton boton-secundario"
                       href="../usuarios/perfil.php?id=<?= (int) $servicio['id_freelancer'] ?>">Ver perfil</a>

                    <?php if ($rol === 'cliente' && (int) $servicio['disponible']): ?>
                        <a class="boton"
                           href="../solicitudes/nueva_solicitud.php?id_servicio=<?= (int) $servicio['id_servicio'] ?>">Contratar</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php cerrar_pagina(); ?>
