<?php
require_once __DIR__ . '/../../includes/sesion.php';

cerrar_sesion_usuario();

header('Location: ' . URL_BASE . '/modulos/usuarios/login.php?salio=1');
exit;