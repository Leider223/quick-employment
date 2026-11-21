<?php
// =====================================================================
// modulos/usuarios/disponibilidad.php
// CU11 - Gestionar disponibilidad (RF12)
//
// Flujo principal del documento:
//   1. El freelancer entra a disponibilidad.
//   2. Elige días y horarios, o se marca como no disponible.
//   3. El sistema guarda la información.
// Flujo alterno: si el horario no es válido, muestra un error.
// Postcondición: la disponibilidad se ve en su perfil (CU03).
//
// Decisiones técnicas:
//   - Solo el freelancer puede entrar (requerir_rol).
//   - El id del freelancer sale de la sesión, nunca del formulario.
//   - El interruptor "disponible" vive en perfiles_freelancer y es el
//     mismo dato que usa buscar.php para ocultar el botón Contratar.
//   - Los horarios son filas de la tabla disponibilidad. Se valida que
//     la hora final sea mayor que la inicial y que no se crucen.
// =====================================================================

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/plantilla.php';

requerir_rol('freelancer');

$idFreelancer = (int) $_SESSION['id_usuario'];
$error   = '';
$mensaje = '';

// Días en el mismo orden y con los mismos valores del ENUM de la tabla
$dias = [
    'lunes'     => 'Lunes',
    'martes'    => 'Martes',
    'miercoles' => 'Miércoles',
    'jueves'    => 'Jueves',
    'viernes'   => 'Viernes',
    'sabado'    => 'Sábado',
    'domingo'   => 'Domingo',
];

// Valores que se conservan en el formulario si hay un error
$diaSel    = '';
$inicioSel = '';
$finSel    = '';

// Estado actual del interruptor (si no hay fila de perfil, esta disponible)
$consulta = $pdo->prepare(
    "SELECT disponible
    FROM perfiles_freelancer
    WHERE id_usuario = ?"
);
$consulta->execute([$idFreelancer]);
$disponible = (int) ($consulta->fetchColumn() ?? 1);

// ---------------------------------------------------------------------
// Acciones (POST)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ---- Marcarse disponible / no disponible -----------------------
    if ($accion === 'cambiar_estado') {
        // El nuevo valor se calcula en el servidor: no se confia en un
        // valor escondido en el formulario.
        $nuevoEstado = $disponible ? 0 : 1;

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO perfiles_freelancer (id_usuario, disponible)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE disponible = VALUES(disponible)"
            );
            $stmt->execute([$idFreelancer, $nuevoEstado]);

            header('Location: disponibilidad.php?estado=1');
            exit;
        } catch (PDOException $e) {
            error_log('Error al cambiar disponibilidad: ' . $e->getMessage());
            $error = 'No se pudo cambiar tu disponibilidad.';
        }
    }

    // ---- Agregar una franja de horario -----------------------------
    elseif ($accion === 'agregar') {
        $dia    = $_POST['dia'] ?? '';
        $inicio = $_POST['hora_inicio'] ?? '';
        $fin    = $_POST['hora_fin'] ?? '';

        $diaSel    = $dia;
        $inicioSel = $inicio;
        $finSel    = $fin;

        $formatoHora = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';

        if (!isset($dias[$dia])) {
            $error = 'Selecciona un día válido.';
        } elseif (
            !preg_match($formatoHora, $inicio) ||
            !preg_match($formatoHora, $fin)
        ) {
            $error = 'Escribe horas válidas en formato HH:MM.';
        } elseif ($inicio >= $fin) {
            $error = 'La hora final debe ser mayor que la hora inicial.';
        } else {
            // En la base las horas son TIME, por eso se agregan los segundos
            $inicioDb = $inicio . ':00';
            $finDb    = $fin . ':00';

            try {
                // Dos franjas del mismo dia se cruzan si una empieza antes
                // de que la otra termine y termina despues de que empiece.
                $choque = $pdo->prepare(
                    "SELECT COUNT(*)
                    FROM disponibilidad
                    WHERE id_freelancer = ?
                      AND dia = ?
                      AND hora_inicio < ?
                      AND hora_fin > ?"
                );
                $choque->execute([
                    $idFreelancer,
                    $dia,
                    $finDb,
                    $inicioDb
                ]);

                if ((int) $choque->fetchColumn() > 0) {
                    $error = 'Ese horario se cruza con otro que ya tienes ese día.';
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO disponibilidad
                            (id_freelancer, dia, hora_inicio, hora_fin)
                        VALUES (?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        $idFreelancer,
                        $dia,
                        $inicioDb,
                        $finDb
                    ]);

                    header('Location: disponibilidad.php?agregado=1');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Error al agregar disponibilidad: ' . $e->getMessage());
                $error = 'No se pudo guardar el horario.';
            }
        }
    }

    // ---- Quitar una franja -----------------------------------------
    elseif ($accion === 'eliminar') {
        $idFranja = filter_input(
            INPUT_POST,
            'id_disponibilidad',
            FILTER_VALIDATE_INT
        );

        if (!$idFranja) {
            $error = 'El horario seleccionado no es válido.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "DELETE FROM disponibilidad
                    WHERE id_disponibilidad = ? AND id_freelancer = ?"
                );
                $stmt->execute([$idFranja, $idFreelancer]);

                if ($stmt->rowCount() === 1) {
                    header('Location: disponibilidad.php?eliminado=1');
                    exit;
                }

                $error = 'El horario no existe o no es tuyo.';
            } catch (PDOException $e) {
                error_log('Error al eliminar disponibilidad: ' . $e->getMessage());
                $error = 'No se pudo quitar el horario.';
            }
        }
    } else {
        $error = 'La acción solicitada no es válida.';
    }
}

// ---------------------------------------------------------------------
// Mensajes despues de redirigir
// ---------------------------------------------------------------------
if (isset($_GET['estado'])) {
    $mensaje = 'Estado de disponibilidad actualizado.';
} elseif (isset($_GET['agregado'])) {
    $mensaje = 'Horario agregado correctamente.';
} elseif (isset($_GET['eliminado'])) {
    $mensaje = 'Horario eliminado.';
}

// ---------------------------------------------------------------------
// Horarios actuales del freelancer
// ---------------------------------------------------------------------
$consulta = $pdo->prepare(
    "SELECT id_disponibilidad, dia, hora_inicio, hora_fin
    FROM disponibilidad
    WHERE id_freelancer = ?
    ORDER BY FIELD(dia, 'lunes', 'martes', 'miercoles', 'jueves',
                   'viernes', 'sabado', 'domingo'),
             hora_inicio"
);
$consulta->execute([$idFreelancer]);
$horarios = $consulta->fetchAll();

abrir_pagina('Mi disponibilidad', $pdo);
?>

<div class="tarjeta">
    <h1>Mi disponibilidad</h1>

    <p>
        <?php if ($disponible): ?>
            <span class="estado estado-aceptada">Disponible</span>
            Estás recibiendo solicitudes: tus servicios muestran el botón
            <strong>Contratar</strong>.
        <?php else: ?>
            <span class="estado estado-cancelada">No disponible</span>
            Tus servicios siguen publicados, pero los clientes no ven el
            botón <strong>Contratar</strong>.
        <?php endif; ?>
    </p>

    <form method="POST" action="disponibilidad.php" class="acciones">
        <input type="hidden" name="accion" value="cambiar_estado">
        <button type="submit" class="boton">
            <?= $disponible
                ? 'Marcarme como NO disponible'
                : 'Marcarme como disponible' ?>
        </button>
        <a class="boton boton-secundario"
           href="perfil.php?id=<?= $idFreelancer ?>">
            Ver mi perfil público
        </a>
    </form>
</div>

<?php if ($mensaje): ?>
    <div class="alerta alerta-exito">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alerta alerta-error">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="tarjeta">
    <h2>Mis horarios</h2>

    <?php if (empty($horarios)): ?>
        <div class="alerta alerta-aviso">
            Todavía no has agregado horarios. Agrega al menos uno para que
            los clientes sepan cuándo puedes trabajar.
        </div>
    <?php else: ?>
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Desde</th>
                        <th>Hasta</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($horarios as $horario): ?>
                        <tr>
                            <td><?= htmlspecialchars($dias[$horario['dia']] ?? $horario['dia'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(substr($horario['hora_inicio'], 0, 5), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(substr($horario['hora_fin'], 0, 5), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="POST" action="disponibilidad.php"
                                      class="acciones"
                                      onsubmit="return confirm('¿Quitar este horario?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input
                                        type="hidden"
                                        name="id_disponibilidad"
                                        value="<?= (int) $horario['id_disponibilidad'] ?>"
                                    >
                                    <button type="submit" class="boton boton-peligro">
                                        Quitar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="tarjeta">
    <h2>Agregar horario</h2>

    <form method="POST" action="disponibilidad.php">
        <input type="hidden" name="accion" value="agregar">

        <label for="dia">Día</label>
        <select name="dia" id="dia" required>
            <option value="">Selecciona un día</option>
            <?php foreach ($dias as $valor => $nombre): ?>
                <option
                    value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $diaSel === $valor ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="hora_inicio">Hora de inicio</label>
        <input
            type="time"
            name="hora_inicio"
            id="hora_inicio"
            step="60"
            required
            value="<?= htmlspecialchars($inicioSel, ENT_QUOTES, 'UTF-8') ?>"
        >

        <label for="hora_fin">Hora de fin</label>
        <input
            type="time"
            name="hora_fin"
            id="hora_fin"
            step="60"
            required
            value="<?= htmlspecialchars($finSel, ENT_QUOTES, 'UTF-8') ?>"
        >

        <button type="submit" class="boton">Agregar horario</button>
    </form>
</div>

<?php cerrar_pagina(); ?>
