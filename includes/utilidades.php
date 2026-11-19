<?php

function post_texto(string $campo): string
{
    $valor = $_POST[$campo] ?? '';

    return is_string($valor) ? trim($valor) : '';
}