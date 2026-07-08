<?php
require_once 'includes/session.php';
require_once 'app/config/conexion.php';

$mensaje = '';
$tipo_mensaje = '';

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    if (!empty($password_actual) && !empty($password_nueva) && !empty($password_confirmar)) {
        if ($password_nueva !== $password_confirmar) {
            $mensaje = "La nueva contraseña y la confirmación no coinciden.";
            $tipo_mensaje = "danger";
        } elseif (strlen($password_nueva) < 6) {
            $mensaje = "La nueva contraseña debe tener al menos 6 caracteres.";
            $tipo_mensaje = "danger";
        } else {
            // Obtener contraseña actual de la BD
            $stmt = $conexion->prepare("SELECT contraseña FROM usuario WHERE id_usuario = ?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res && $res->num_rows > 0) {
                $user = $res->fetch_assoc();
                
                // Verificar si coincide con password_verify
                if (password_verify($password_actual, $user['contraseña'])) {
                    // Cifrar la nueva contraseña
                    $nueva_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                    
                    // Actualizar en BD
                    $stmt_update = $conexion->prepare("UPDATE usuario SET contraseña = ? WHERE id_usuario = ?");
                    $stmt_update->bind_param("si", $nueva_hash, $id_usuario);
                    
                    if ($stmt_update->execute()) {
                        $mensaje = "Contraseña cambiada con éxito.";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al actualizar la contraseña en la base de datos.";
                        $tipo_mensaje = "danger";
                    }
                    $stmt_update->close();
                } else {
                    $mensaje = "La contraseña actual es incorrecta.";
                    $tipo_mensaje = "danger";
                }
            } else {
                $mensaje = "No se encontró el registro del usuario.";
                $tipo_mensaje = "danger";
            }
            $stmt->close();
        }
    } else {
        $mensaje = "Por favor complete todos los campos.";
        $tipo_mensaje = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConectaWork | Configuración de Cuenta</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS del Sistema -->
    <link rel="stylesheet" href="public/css/layout.css">
    <link rel="stylesheet" href="public/css/custom.css">
</head>

<body>
    <div class="layout">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>

        <div class="main">
            <!-- Header -->
            <?php include_once 'includes/header.php'; ?>

            <!-- Área de Contenido Principal -->
            <main class="content">

                <!-- Sección de Título -->
                <div>
                    <h2 style="font-size: 1.6rem; font-weight: 700;">Configuración de Cuenta</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Gestiona la seguridad y credenciales de acceso de tu usuario.</p>
                </div>

                <!-- Mensajes de feedback -->
                <?php if (!empty($mensaje)): ?>
                    <div class="alert-custom alert-custom-<?php echo $tipo_mensaje; ?>" style="max-width: 600px;">
                        <i class="fa-solid <?php echo ($tipo_mensaje === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                        <span><?php echo htmlspecialchars($mensaje); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Formulario de Seguridad -->
                <div class="content-card" style="max-width: 600px;">
                    <div class="content-card-header">
                        <h3 class="content-card-title">
                            <i class="fa-solid fa-shield-halved" style="color: var(--primary);"></i>
                            Cambiar Contraseña
                        </h3>
                    </div>
                    
                    <form method="POST" action="configuracion.php">
                        <div class="form-group-custom">
                            <label>Contraseña Actual *</label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <i class="fa-solid fa-lock" style="position: absolute; left: 14px; color: var(--text-muted);"></i>
                                <input type="password" name="password_actual" class="form-control-custom" required placeholder="********" style="padding-left: 38px;">
                            </div>
                        </div>

                        <div class="form-group-custom">
                            <label>Nueva Contraseña *</label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <i class="fa-solid fa-key" style="position: absolute; left: 14px; color: var(--text-muted);"></i>
                                <input type="password" name="password_nueva" class="form-control-custom" required placeholder="Min. 6 caracteres" style="padding-left: 38px;">
                            </div>
                            <span style="font-size:0.7rem; color:var(--text-muted); display:block; margin-top:4px;">Usa combinaciones de letras, números y caracteres especiales.</span>
                        </div>

                        <div class="form-group-custom">
                            <label>Confirmar Nueva Contraseña *</label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <i class="fa-solid fa-key" style="position: absolute; left: 14px; color: var(--text-muted);"></i>
                                <input type="password" name="password_confirmar" class="form-control-custom" required placeholder="Min. 6 caracteres" style="padding-left: 38px;">
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="fa-solid fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

            </main>

            <!-- Footer -->
            <?php include_once 'includes/footer.php'; ?>
        </div>
    </div>
</body>

</html>
