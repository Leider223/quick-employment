<?php
// =====================================================================
// includes/utilidades.php
// Funciones pequeñas que se usan en varias páginas.
// =====================================================================

// Devuelve el texto de un campo POST sin espacios sobrantes.
// Si el campo no llegó o llegó como arreglo, devuelve cadena vacía.
function post_texto(string $campo): string
{
    $valor = $_POST[$campo] ?? '';

    return is_string($valor) ? trim($valor) : '';
}

// Devuelve la clase CSS que pinta la etiqueta del estado de una solicitud
// (las clases están definidas en css/estilos.css).
function clase_estado(string $estado): string
{
    $validos = ['pendiente', 'aceptada', 'rechazada', 'cancelada', 'finalizada'];

    return in_array($estado, $validos, true) ? 'estado-' . $estado : '';
}

// 2026-12-01  ->  01/12/2026
function fecha_corta(?string $fecha): string
{
    if ($fecha === null || $fecha === '' || $fecha === '0000-00-00') {
        return '';
    }

    return date('d/m/Y', strtotime($fecha));
}

// 2026-12-01 08:30:00  ->  01/12/2026 08:30
function fecha_hora(?string $fecha): string
{
    if ($fecha === null || $fecha === '') {
        return '';
    }

    return date('d/m/Y H:i', strtotime($fecha));
}
