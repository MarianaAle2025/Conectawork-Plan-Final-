<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya tiene sesión activa, redirigir al Dashboard
if (isset($_SESSION['id_usuario'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'app/config/conexion.php';
    
    $correo = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($correo) && !empty($password)) {
        // Consultar usuario por correo utilizando Prepared Statements
        $stmt = $conexion->prepare("
            SELECT u.id_usuario, u.correo, u.contraseña, u.activo, u.id_rol, r.nombre AS rol_nombre, e.id_empleado, e.nombre, e.apellido, e.cargo
            FROM usuario u
            INNER JOIN rol r ON u.id_rol = r.id_rol
            LEFT JOIN empleado e ON u.id_usuario = e.id_usuario
            WHERE u.correo = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verificar si el usuario está activo
                if ($user['activo'] == 0) {
                    $error = 'Tu cuenta de usuario está inactiva. Contacta al administrador.';
                } else {
                    // Validar contraseña con password_verify
                    if (password_verify($password, $user['contraseña'])) {
                        // Almacenar datos en sesión
                        $_SESSION['id_usuario'] = $user['id_usuario'];
                        $_SESSION['id_empleado'] = $user['id_empleado'];
                        $_SESSION['correo'] = $user['correo'];
                        $_SESSION['nombre'] = $user['nombre'] ?? 'Usuario';
                        $_SESSION['apellido'] = $user['apellido'] ?? '';
                        $_SESSION['id_rol'] = $user['id_rol'];
                        $_SESSION['rol_nombre'] = $user['rol_nombre'];
                        $_SESSION['cargo'] = $user['cargo'] ?? 'Colaborador';
                        
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        $error = 'Contraseña incorrecta.';
                    }
                }
            } else {
                $error = 'El correo electrónico no está registrado.';
            }
            $stmt->close();
        } else {
            $error = 'Error de base de datos. Intente más tarde.';
        }
    } else {
        $error = 'Por favor complete todos los campos.';
    }
}

// Cargar la vista de login
require_once "app/views/auth/login.php";