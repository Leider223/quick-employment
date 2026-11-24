<?php

// Busca los datos publicos de un freelancer
function obtener_perfil_freelancer($con, $id_freelancer)
{
    $id_freelancer = (int) $id_freelancer;

    $query = "SELECT u.id_usuario, u.nombre, p.presentacion, p.experiencia, p.disponible
              FROM usuarios u
              LEFT JOIN perfiles_freelancer p ON p.id_usuario = u.id_usuario
              WHERE u.id_usuario = $id_freelancer AND u.rol = 'freelancer'";

    $resultado = mysqli_query($con, $query) or die(mysqli_error($con));
    $perfil = mysqli_fetch_assoc($resultado);

    // Devuelve null si no encontro el freelancer
    return $perfil ?: null;
}

// Lista las habilidades de un freelancer, ordenadas por nombre
function obtener_habilidades_freelancer($con, $id_freelancer)
{
    $id_freelancer = (int) $id_freelancer;

    $query = "SELECT h.id_habilidad, h.nombre
              FROM freelancer_habilidades fh
              INNER JOIN habilidades h ON h.id_habilidad = fh.id_habilidad
              WHERE fh.id_freelancer = $id_freelancer
              ORDER BY h.nombre";

    $resultado = mysqli_query($con, $query) or die(mysqli_error($con));

    return mysqli_fetch_all($resultado, MYSQLI_ASSOC);
}
