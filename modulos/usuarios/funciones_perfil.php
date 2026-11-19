<?php
// =====================================================================
// modulos/usuarios/funciones_perfil.php
// Funciones de CONSULTA del perfil de un freelancer.
// Las usa el Integrante 2 para armar el perfil público (RF04).
//
// Cómo se usan desde otro módulo:
//     require_once __DIR__ . '/../usuarios/funciones_perfil.php';
//     $perfil      = obtener_perfil_freelancer($pdo, $id_freelancer);
//     $habilidades = obtener_habilidades_freelancer($pdo, $id_freelancer);
//
// Reglas:
//   - Solo LEEN datos. No escriben en la base ni imprimen HTML.
//   - Los textos que devuelven NO están escapados: quien los muestre en
//     pantalla debe usar htmlspecialchars().
//   - Nunca devuelven el correo ni la contraseña: es información pública.
//   - Si el freelancer no tiene fila en perfiles_freelancer, presentacion,
//     experiencia y disponible llegan como NULL.
//
// Responsable: Integrante 1
// =====================================================================

// Devuelve los datos públicos de un freelancer, o null si no existe
// (o si ese id pertenece a un cliente).
// Lista devuelta: id_usuario, nombre, presentacion, experiencia, disponible.
function obtener_perfil_freelancer(PDO $pdo, int $id_freelancer): ?array
{
    $consulta = $pdo->prepare(
        'SELECT u.id_usuario, u.nombre, p.presentacion, p.experiencia, p.disponible
         FROM usuarios u
         LEFT JOIN perfiles_freelancer p ON p.id_usuario = u.id_usuario
         WHERE u.id_usuario = :id AND u.rol = :rol'
    );
    $consulta->execute([':id' => $id_freelancer, ':rol' => 'freelancer']);
    $perfil = $consulta->fetch();

    // fetch() devuelve false si no hay fila; lo convertimos en null
    return $perfil ?: null;
}

// Devuelve la lista de habilidades de un freelancer, ordenadas por nombre.
// Cada elemento tiene: id_habilidad, nombre. Si no tiene ninguna, devuelve [].
function obtener_habilidades_freelancer(PDO $pdo, int $id_freelancer): array
{
    $consulta = $pdo->prepare(
        'SELECT h.id_habilidad, h.nombre
         FROM freelancer_habilidades fh
         INNER JOIN habilidades h ON h.id_habilidad = fh.id_habilidad
         WHERE fh.id_freelancer = :id
         ORDER BY h.nombre'
    );
    $consulta->execute([':id' => $id_freelancer]);

    return $consulta->fetchAll();
}