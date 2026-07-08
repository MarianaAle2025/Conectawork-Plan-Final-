<?php
// Evitar duplicar inicio de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirección si no existe sesión activa
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}
