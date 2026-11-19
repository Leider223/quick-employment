<?php
// =====================================================================
// modulos/solicitudes/nueva_solicitud.php
// CU04 - Enviar solicitud de contratación (RF05)
//
// Flujo principal:
//   1. El cliente pulsa «Contratar».
//   2. Escribe lo que necesita y la fecha.
//   3. El sistema revisa los datos.
//   4. El sistema guarda la solicitud como «Pendiente».
//   5. El sistema avisa al freelancer (CU13).
// Flujo alterno: "Si el freelancer no está disponible, el sistema avisa
// y muestra otros freelancers".
//
// Puntos importantes:
//   - La disponibilidad se revisa antes de mostrar el formulario y otra
//     vez al guardar: el freelancer pudo marcarse como no disponible
//     mientras el cliente escribía.
//   - El INSERT de la solicitud y el aviso al freelancer van en la misma
//     transacción (o quedan los dos, o ninguno).
// =====================================================================

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';
require_once __DIR__ . '/../../includes/utilidades.php';

requerir_rol('cliente');

$id_servicio = (int) ($_GET['id_servicio'] ?? 0);

$consulta = $pdo->prepare(
    'SELECT s.id_servicio, s.titulo, s.descripcion, s.precio, s.id_categoria,
            s.id_freelancer, u.nombre AS freelancer,
            COALESCE(p.disponible, 1) AS disponible
    FROM servicios s
    INNER JOIN usuarios u ON u.id_usuario = s.id_freelancer
    LEFT JOIN perfiles_freelancer p ON p.id_usuario = s.id_freelancer
    WHERE s.id_servicio = :id AND s.estado = :estado'
);
$consulta->execute([':id' => $id_servicio, ':estado' => 'activo']);
$servicio = $consulta->fetch();

$disponible = $servicio ? (int) $servicio['disponible'] === 1 : false;

// Servicios de otros freelancers disponibles, para ofrecer alternativas.
// Primero los de la misma categoría.
$alternativas = [];
if ($servicio && !$disponible) {
    $consulta = $pdo->prepare(
        'SELECT s.id_servicio, s.titulo, s.precio, u.nombre AS freelancer
         FROM servicios s
         INNER JOIN usuarios u ON u.id_usuario = s.id_freelancer
         LEFT JOIN perfiles_freelancer p ON p.id_usuario = s.id_freelancer
         WHERE s.estado = :estado
           AND s.id_servicio <> :id_servicio
           AND COALESCE(p.disponible, 1) = 1
         ORDER BY (s.id_categoria = :categoria) DESC, s.fecha_publicacion DESC
         LIMIT 5'
    );
    $consulta->execute([
        ':estado'      => 'activo',
        ':id_servicio' => $servicio['id_servicio'],
        ':categoria'   => $servicio['id_categoria'],
    ]);
    $alternativas = $consulta->fetchAll();
}

$errores = [];
$descripcion = '';
$fecha_requerida = '';

if ($servicio && $disponible && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion     = str_replace("\r\n", "\n", post_texto('descripcion'));
    $fecha_requerida = post_texto('fecha_requerida');

    if (mb_strlen($descripcion) < 10 || mb_strlen($descripcion) > 1000) {
        $errores[] = 'Describe lo que necesitas (entre 10 y 1000 caracteres).';
    }

    $fecha = DateTime::createFromFormat('Y-m-d', $fecha_requerida);
    if (!$fecha || $fecha->format('Y-m-d') !== $fecha_requerida) {
        $errores[] = 'Elige una fecha válida.';
    } elseif ($fecha_requerida < date('Y-m-d')) {
        $errores[] = 'La fecha no puede ser anterior a hoy.';
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            // estado, fecha_solicitud y las confirmaciones toman su valor
            // por defecto (pendiente, ahora, 0 y 0)
            $guardar = $pdo->prepare(
                'INSERT INTO solicitudes (id_cliente, id_servicio, descripcion, fecha_requerida)
                 VALUES (:cliente, :servicio, :descripcion, :fecha)'
            );
            $guardar->execute([
                ':cliente'     => $_SESSION['id_usuario'],
                ':servicio'    => $servicio['id_servicio'],
                ':descripcion' => $descripcion,
                ':fecha'       => $fecha_requerida,
            ]);

            $id_solicitud = (int) $pdo->lastInsertId();

            // Paso 5 del CU04: avisa al freelancer (CU13 / RF14)
            crear_notificacion(
                $pdo,
                (int) $servicio['id_freelancer'],
                $id_solicitud,
                'Tienes una nueva solicitud para tu servicio «' . $servicio['titulo'] . '».'
            );

            $pdo->commit();

            header('Location: mis_solicitudes.php?enviada=1');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al guardar la solicitud: ' . $e->getMessage());
            $errores[] = 'No se pudo enviar la solicitud. Inténtalo de nuevo.';
        }
    }
}

abrir_pagina('Contratar servicio', $pdo);
?>

<?php if (!$servicio): ?>
    <div class="tarjeta">
        <h1>Contratar servicio</h1>
        <div class="alerta alerta-aviso">Este servicio no existe o ya no está disponible.</div>
        <a class="boton" href="../servicios/buscar.php">Volver a la búsqueda</a>
    </div>

<?php elseif (!$disponible): ?>
    <div class="tarjeta">
        <h1><?= htmlspecialchars($servicio['titulo']) ?></h1>
        <div class="alerta alerta-aviso">
            <strong><?= htmlspecialchars($servicio['freelancer']) ?></strong> no está disponible
            en este momento, así que no se puede enviar la solicitud.
        </div>

        <?php if (!empty($alternativas)): ?>
            <h2>Otros freelancers disponibles</h2>
            <ul>
                <?php foreach ($alternativas as $alternativa): ?>
                    <li>
                        <a href="nueva_solicitud.php?id_servicio=<?= (int) $alternativa['id_servicio'] ?>">
                            <?= htmlspecialchars($alternativa['titulo']) ?>
                        </a>
                        — <?= htmlspecialchars($alternativa['freelancer']) ?>
                        ($<?= number_format($alternativa['precio'], 0, ',', '.') ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Todavía no hay otros servicios disponibles.</p>
        <?php endif; ?>

        <a class="boton" href="../servicios/buscar.php">Volver a la búsqueda</a>
    </div>

<?php else: ?>
    <div class="tarjeta">
        <h1>Contratar: <?= htmlspecialchars($servicio['titulo']) ?></h1>
        <p><strong>Freelancer:</strong> <?= htmlspecialchars($servicio['freelancer']) ?></p>
        <p><strong>Precio:</strong>
            <span class="precio">$<?= number_format($servicio['precio'], 0, ',', '.') ?></span>
        </p>
        <p><?= nl2br(htmlspecialchars($servicio['descripcion'])) ?></p>
    </div>

    <div class="tarjeta">
        <h2>Datos de la solicitud</h2>

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
            <label for="descripcion">¿Qué necesitas?</label>
            <textarea id="descripcion" name="descripcion" rows="5" maxlength="1000"><?= htmlspecialchars($descripcion) ?></textarea>

            <label for="fecha_requerida">¿Para qué fecha?</label>
            <input type="date" id="fecha_requerida" name="fecha_requerida"
                   min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($fecha_requerida) ?>" required>

            <button type="submit" class="boton">Enviar solicitud</button>
        </form>
    </div>
<?php endif; ?>

<?php cerrar_pagina(); ?>
