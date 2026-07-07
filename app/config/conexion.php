<?php

$host = "localhost";
$usuario = "root";
$password = "";
$basedatos = "bd_conectawork";

$conexion = new mysqli($host, $usuario, $password, $basedatos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Opcional: establecer UTF-8
$conexion->set_charset("utf8");