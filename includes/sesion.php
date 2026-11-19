<?php



if (!defined('URL_BASE')) {
    define('URL_BASE', '/quick-employment');
}


if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}


function usuario_logueado(): bool
{
    return isset($_SESSION['id_usuario']);
}


function iniciar_sesion_usuario(array $usuario): void
{
    
    session_regenerate_id(true);

    $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
    $_SESSION['rol']        = $usuario['rol'];
    $_SESSION['nombre']     = $usuario['nombre'];
}


function cerrar_sesion_usuario(): void
{
    $_SESSION = [];

    
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}


function requerir_login(): void
{
    if (!usuario_logueado()) {
        header('Location: ' . URL_BASE . '/modulos/usuarios/login.php');
        exit;
    }
}


function requerir_rol(string $rol): void
{
    requerir_login();

    if ($_SESSION['rol'] !== $rol) {
        http_response_code(403);
        die('No tienes permiso para ver esta página.');
    }
}
