<?php
require_once 'includes/session.php';
require_once 'app/config/conexion.php';

// Validar que el usuario sea Administrador
if ($_SESSION['id_rol'] != 1) {
    header("Location: dashboard.php");
    exit;
}

$mensaje = '';
$tipo_mensaje = ''; // 'success' o 'danger'

// ========================================================
// PROCESAMIENTO DE ACCIONES (POST)
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // CREAR EMPLEADO
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $documento = trim($_POST['documento'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $id_area = intval($_POST['id_area'] ?? 0);
        $id_empresa = intval($_POST['id_empresa'] ?? 0);
        $id_jefe = !empty($_POST['id_jefe']) ? intval($_POST['id_jefe']) : null;
        $id_rol = intval($_POST['id_rol'] ?? 2);
        $activo = intval($_POST['activo'] ?? 1);
        $fecha_ingreso = $_POST['fecha_ingreso'] ?? date('Y-m-d');
        $modalidad = $_POST['modalidad_trabajo'] ?? 'Remoto';
        $password = $_POST['password'] ?? '';

        if (!empty($nombre) && !empty($apellido) && !empty($documento) && !empty($correo) && !empty($password)) {
            
            // Iniciar Transacción
            $conexion->begin_transaction();
            try {
                // 1. Validar que el correo no esté duplicado
                $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
                $stmt->bind_param("s", $correo);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("El correo electrónico ya está registrado.");
                }
                $stmt->close();

                // 2. Validar que el documento no esté duplicado
                $stmt = $conexion->prepare("SELECT id_empleado FROM empleado WHERE documento = ?");
                $stmt->bind_param("s", $documento);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("El documento de identidad ya está registrado.");
                }
                $stmt->close();

                // 3. Crear usuario
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("INSERT INTO usuario (correo, contraseña, activo, id_rol) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $correo, $hashed_pass, $activo, $id_rol);
                $stmt->execute();
                $id_usuario = $conexion->insert_id;
                $stmt->close();

                // 4. Crear empleado
                $stmt = $conexion->prepare("
                    INSERT INTO empleado (id_usuario, id_area, nombre, apellido, documento, telefono, correo_empresarial, cargo, fecha_ingreso, modalidad_trabajo, id_empresa, id_jefe)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iissssssssii", $id_usuario, $id_area, $nombre, $apellido, $documento, $telefono, $correo, $cargo, $fecha_ingreso, $modalidad, $id_empresa, $id_jefe);
                $stmt->execute();
                $stmt->close();

                // Commit si todo sale bien
                $conexion->commit();
                $mensaje = "Empleado creado correctamente.";
                $tipo_mensaje = "success";
            } catch (Exception $e) {
                $conexion->rollback();
                $mensaje = "Error al crear empleado: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        } else {
            $mensaje = "Por favor complete todos los campos obligatorios.";
            $tipo_mensaje = "danger";
        }
    }

    // EDITAR EMPLEADO
    elseif ($accion === 'editar') {
        $id_empleado = intval($_POST['id_empleado'] ?? 0);
        $id_usuario = intval($_POST['id_usuario'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $documento = trim($_POST['documento'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $id_area = intval($_POST['id_area'] ?? 0);
        $id_empresa = intval($_POST['id_empresa'] ?? 0);
        $id_jefe = !empty($_POST['id_jefe']) ? intval($_POST['id_jefe']) : null;
        $id_rol = intval($_POST['id_rol'] ?? 2);
        $activo = intval($_POST['activo'] ?? 1);
        $fecha_ingreso = $_POST['fecha_ingreso'] ?? date('Y-m-d');
        $modalidad = $_POST['modalidad_trabajo'] ?? 'Remoto';
        $password = $_POST['password'] ?? '';

        if ($id_empleado > 0 && $id_usuario > 0 && !empty($nombre) && !empty($apellido) && !empty($documento) && !empty($correo)) {
            
            $conexion->begin_transaction();
            try {
                // 1. Validar duplicado de correo excluyendo el actual
                $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = ? AND id_usuario != ?");
                $stmt->bind_param("si", $correo, $id_usuario);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("El correo electrónico ya está registrado por otro usuario.");
                }
                $stmt->close();

                // 2. Validar duplicado de documento excluyendo el actual
                $stmt = $conexion->prepare("SELECT id_empleado FROM empleado WHERE documento = ? AND id_empleado != ?");
                $stmt->bind_param("si", $documento, $id_empleado);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("El documento de identidad ya está registrado por otro empleado.");
                }
                $stmt->close();

                // 3. Actualizar usuario
                if (!empty($password)) {
                    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conexion->prepare("UPDATE usuario SET correo = ?, contraseña = ?, activo = ?, id_rol = ? WHERE id_usuario = ?");
                    $stmt->bind_param("ssiii", $correo, $hashed_pass, $activo, $id_rol, $id_usuario);
                } else {
                    $stmt = $conexion->prepare("UPDATE usuario SET correo = ?, activo = ?, id_rol = ? WHERE id_usuario = ?");
                    $stmt->bind_param("siii", $correo, $activo, $id_rol, $id_usuario);
                }
                $stmt->execute();
                $stmt->close();

                // 4. Actualizar empleado
                $stmt = $conexion->prepare("
                    UPDATE empleado 
                    SET id_area = ?, nombre = ?, apellido = ?, documento = ?, telefono = ?, correo_empresarial = ?, cargo = ?, fecha_ingreso = ?, modalidad_trabajo = ?, id_empresa = ?, id_jefe = ?
                    WHERE id_empleado = ?
                ");
                $stmt->bind_param("issssssssiii", $id_area, $nombre, $apellido, $documento, $telefono, $correo, $cargo, $fecha_ingreso, $modalidad, $id_empresa, $id_jefe, $id_empleado);
                $stmt->execute();
                $stmt->close();

                $conexion->commit();
                $mensaje = "Empleado actualizado correctamente.";
                $tipo_mensaje = "success";
            } catch (Exception $e) {
                $conexion->rollback();
                $mensaje = "Error al actualizar empleado: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        } else {
            $mensaje = "Por favor complete todos los campos obligatorios.";
            $tipo_mensaje = "danger";
        }
    }

    // ELIMINAR EMPLEADO
    elseif ($accion === 'eliminar') {
        $id_empleado = intval($_POST['id_empleado'] ?? 0);
        $id_usuario = intval($_POST['id_usuario'] ?? 0);

        if ($id_empleado > 0 && $id_usuario > 0) {
            
            // Evitar que el administrador se elimine a sí mismo
            if ($id_usuario == $_SESSION['id_usuario']) {
                $mensaje = "No puedes eliminar tu propio usuario administrador.";
                $tipo_mensaje = "danger";
            } else {
                $conexion->begin_transaction();
                try {
                    // Desvincular de reportes de actividades (poner jefe inmediato en null o cascada si aplica)
                    $stmt = $conexion->prepare("UPDATE reportes_actividades SET id_jefe = NULL WHERE id_jefe = ?");
                    $stmt->bind_param("i", $id_empleado);
                    $stmt->execute();
                    $stmt->close();

                    // Poner jefe inmediato en null para empleados a su cargo
                    $stmt = $conexion->prepare("UPDATE empleado SET id_jefe = NULL WHERE id_jefe = ?");
                    $stmt->bind_param("i", $id_empleado);
                    $stmt->execute();
                    $stmt->close();

                    // Borrar empleado
                    $stmt = $conexion->prepare("DELETE FROM empleado WHERE id_empleado = ?");
                    $stmt->bind_param("i", $id_empleado);
                    $stmt->execute();
                    $stmt->close();

                    // Borrar usuario
                    $stmt = $conexion->prepare("DELETE FROM usuario WHERE id_usuario = ?");
                    $stmt->bind_param("i", $id_usuario);
                    $stmt->execute();
                    $stmt->close();

                    $conexion->commit();
                    $mensaje = "Empleado eliminado de la base de datos.";
                    $tipo_mensaje = "success";
                } catch (Exception $e) {
                    $conexion->rollback();
                    $mensaje = "Error al eliminar empleado: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
        }
    }

    // ACTIVAR / INACTIVAR RAPIDO
    elseif ($accion === 'toggle_estado') {
        $id_usuario = intval($_POST['id_usuario'] ?? 0);
        $nuevo_estado = intval($_POST['nuevo_estado'] ?? 1);

        if ($id_usuario > 0) {
            if ($id_usuario == $_SESSION['id_usuario']) {
                $mensaje = "No puedes desactivar tu propio usuario administrador.";
                $tipo_mensaje = "danger";
            } else {
                $stmt = $conexion->prepare("UPDATE usuario SET activo = ? WHERE id_usuario = ?");
                $stmt->bind_param("ii", $nuevo_estado, $id_usuario);
                if ($stmt->execute()) {
                    $mensaje = "Estado del usuario actualizado.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar estado.";
                    $tipo_mensaje = "danger";
                }
                $stmt->close();
            }
        }
    }
}

// ========================================================
// OBTENER INFORMACIÓN DE LA BASE DE DATOS
// ========================================================

// 1. Buscador y Filtros
$busqueda = trim($_GET['buscar'] ?? '');
$sql_buscar = "";
if (!empty($busqueda)) {
    $sql_buscar = " AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.documento LIKE ? OR e.cargo LIKE ?)";
}

// 2. Consulta de Empleados
$query_empleados = "
    SELECT e.*, u.correo, u.activo, u.id_rol, r.nombre AS rol_nombre, a.nombre AS area_nombre, emp.nombre AS empresa_nombre, 
           CONCAT(j.nombre, ' ', j.apellido) AS jefe_nombre
    FROM empleado e
    INNER JOIN usuario u ON e.id_usuario = u.id_usuario
    INNER JOIN rol r ON u.id_rol = r.id_rol
    INNER JOIN area a ON e.id_area = a.id_area
    LEFT JOIN empresas emp ON e.id_empresa = emp.id_empresa
    LEFT JOIN empleado j ON e.id_jefe = j.id_empleado
    WHERE 1=1 $sql_buscar
    ORDER BY e.id_empleado DESC
";

$stmt = $conexion->prepare($query_empleados);
if (!empty($busqueda)) {
    $like_busqueda = "%" . $busqueda . "%";
    $stmt->bind_param("ssss", $like_busqueda, $like_busqueda, $like_busqueda, $like_busqueda);
}
$stmt->execute();
$result_empleados = $stmt->get_result();

// 3. Catálogo de Áreas
$areas = [];
$res_areas = $conexion->query("SELECT * FROM area ORDER BY nombre ASC");
while ($row = $res_areas->fetch_assoc()) {
    $areas[] = $row;
}

// 4. Catálogo de Empresas
$empresas = [];
$res_empresas = $conexion->query("SELECT * FROM empresas ORDER BY nombre ASC");
while ($row = $res_empresas->fetch_assoc()) {
    $empresas[] = $row;
}

// 5. Catálogo de Roles
$roles = [];
$res_roles = $conexion->query("SELECT * FROM rol ORDER BY nombre ASC");
while ($row = $res_roles->fetch_assoc()) {
    $roles[] = $row;
}

// 6. Lista de Jefes posibles (todos los empleados para simplificar)
$jefes = [];
$res_jefes = $conexion->query("SELECT id_empleado, nombre, apellido, cargo FROM empleado ORDER BY nombre ASC");
while ($row = $res_jefes->fetch_assoc()) {
    $jefes[] = $row;
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConectaWork | Gestión de Empleados</title>

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

                <!-- Sección de Título y Botón Agregar -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h2 style="font-size: 1.6rem; font-weight: 700;">Colaboradores y Empleados</h2>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">Registra, modifica y gestiona el talento humano de ConectaWork.</p>
                    </div>
                    <button class="btn-custom btn-custom-primary" onclick="abrirModalCrear()">
                        <i class="fa-solid fa-user-plus"></i> Registrar Empleado
                    </button>
                </div>

                <!-- Mensajes de feedback -->
                <?php if (!empty($mensaje)): ?>
                    <div class="alert-custom alert-custom-<?php echo $tipo_mensaje; ?>">
                        <i class="fa-solid <?php echo ($tipo_mensaje === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                        <span><?php echo htmlspecialchars($mensaje); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Caja de Filtros y Búsqueda -->
                <div class="content-card">
                    <form method="GET" action="empleados.php">
                        <div class="filter-grid">
                            <div style="flex: 1; min-width: 250px;">
                                <label style="display:block; font-size: 0.72rem; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase;">Buscar por Nombre, Cargo o Documento</label>
                                <div style="position: relative; display: flex; align-items: center;">
                                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; color: var(--text-muted);"></i>
                                    <input type="text" name="buscar" class="form-control-custom" placeholder="Ej: Juan Pérez o Desarrollador..." value="<?php echo htmlspecialchars($busqueda); ?>" style="padding-left: 38px;">
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="btn-custom btn-custom-accent" style="width: 100%; height: 42px;">
                                    <i class="fa-solid fa-filter"></i> Filtrar
                                </button>
                            </div>
                            <?php if (!empty($busqueda)): ?>
                                <div>
                                    <a href="empleados.php" class="btn-custom btn-custom-secondary" style="width: 100%; height: 42px; display:flex; align-items:center;">
                                        Limpiar
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Tabla de Resultados -->
                <div class="content-card" style="padding: 16px 0;">
                    <div class="table-responsive-custom">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Colaborador</th>
                                    <th>Documento</th>
                                    <th>Contacto</th>
                                    <th>Área / Cargo</th>
                                    <th>Empresa / Jefe</th>
                                    <th>Rol</th>
                                    <th>Ingreso</th>
                                    <th>Estado</th>
                                    <th style="text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_empleados->num_rows > 0): ?>
                                    <?php while ($emp = $result_empleados->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="width: 36px; height: 36px; border-radius: var(--radius-sm); background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: var(--text-dark); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                                                        <?php 
                                                            echo strtoupper(substr($emp['nombre'], 0, 1) . substr($emp['apellido'], 0, 1));
                                                        ?>
                                                    </div>
                                                    <div>
                                                        <span style="font-weight: 600; display: block;"><?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?></span>
                                                        <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($emp['correo']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span style="font-family: monospace; font-size:0.8rem;"><?php echo htmlspecialchars($emp['documento']); ?></span></td>
                                            <td>
                                                <div style="font-size: 0.78rem;">
                                                    <span><i class="fa-solid fa-phone" style="color:var(--text-muted); font-size:0.7rem; width:14px;"></i> <?php echo htmlspecialchars($emp['telefono']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-weight: 500; display: block;"><?php echo htmlspecialchars($emp['cargo']); ?></span>
                                                <span style="font-size: 0.72rem; color: var(--accent);"><?php echo htmlspecialchars($emp['area_nombre']); ?></span>
                                            </td>
                                            <td>
                                                <span style="display: block; font-size: 0.8rem;"><?php echo htmlspecialchars($emp['empresa_nombre'] ?? 'Sin Asignar'); ?></span>
                                                <span style="font-size: 0.72rem; color: var(--text-muted);">Jefe: <?php echo htmlspecialchars($emp['jefe_nombre'] ?? 'Ninguno'); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge-custom <?php echo ($emp['id_rol'] == 1) ? 'badge-custom-primary' : 'badge-custom-info'; ?>">
                                                    <?php echo htmlspecialchars($emp['rol_nombre']); ?>
                                                </span>
                                            </td>
                                            <td><span style="font-size: 0.78rem;"><?php echo htmlspecialchars(date('d/m/Y', strtotime($emp['fecha_ingreso']))); ?></span></td>
                                            <td>
                                                <form method="POST" action="empleados.php" style="display:inline;">
                                                    <input type="hidden" name="accion" value="toggle_estado">
                                                    <input type="hidden" name="id_usuario" value="<?php echo $emp['id_usuario']; ?>">
                                                    <input type="hidden" name="nuevo_estado" value="<?php echo ($emp['activo'] == 1) ? 0 : 1; ?>">
                                                    <button type="submit" class="badge-custom <?php echo ($emp['activo'] == 1) ? 'badge-custom-success' : 'badge-custom-danger'; ?>" style="border:none; cursor:pointer;" title="Haga clic para cambiar estado">
                                                        <i class="fa-solid <?php echo ($emp['activo'] == 1) ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                                                        <?php echo ($emp['activo'] == 1) ? 'Activo' : 'Inactivo'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 8px; justify-content: center;">
                                                    <!-- Editar -->
                                                    <button class="btn-custom btn-custom-secondary btn-custom-sm" onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($emp)); ?>)" title="Editar Colaborador">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    
                                                    <!-- Eliminar -->
                                                    <form method="POST" action="empleados.php" onsubmit="return confirm('¿Estás seguro de eliminar completamente a este empleado y su cuenta de usuario? Esta acción es irreversible.')" style="display:inline;">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="id_empleado" value="<?php echo $emp['id_empleado']; ?>">
                                                        <input type="hidden" name="id_usuario" value="<?php echo $emp['id_usuario']; ?>">
                                                        <button type="submit" class="btn-custom btn-custom-danger btn-custom-sm" title="Eliminar Colaborador">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                            <i class="fa-regular fa-folder-open" style="font-size: 2rem; display:block; margin-bottom:10px;"></i>
                                            No se encontraron empleados registrados.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>

            <!-- ========================================================
               MODAL CREAR / EDITAR COLABORADOR
               ======================================================== -->
            <div class="modal-custom" id="modalEmpleado">
                <div class="modal-content-custom">
                    <form method="POST" action="empleados.php">
                        <input type="hidden" name="accion" id="formAccion" value="crear">
                        <input type="hidden" name="id_empleado" id="formIdEmpleado" value="">
                        <input type="hidden" name="id_usuario" id="formIdUsuario" value="">

                        <div class="modal-header-custom">
                            <h3 id="modalTitulo">Registrar Nuevo Colaborador</h3>
                            <span class="modal-close-custom" onclick="cerrarModal()">&times;</span>
                        </div>
                        <div class="modal-body-custom">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Nombres *</label>
                                    <input type="text" name="nombre" id="formNombre" class="form-control-custom" required>
                                </div>
                                <div class="form-group-custom">
                                    <label>Apellidos *</label>
                                    <input type="text" name="apellido" id="formApellido" class="form-control-custom" required>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Documento Identidad *</label>
                                    <input type="text" name="documento" id="formDocumento" class="form-control-custom" required>
                                </div>
                                <div class="form-group-custom">
                                    <label>Teléfono</label>
                                    <input type="text" name="telefono" id="formTelefono" class="form-control-custom">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Correo Corporativo *</label>
                                    <input type="email" name="correo" id="formCorreo" class="form-control-custom" required>
                                </div>
                                <div class="form-group-custom">
                                    <label>Cargo *</label>
                                    <input type="text" name="cargo" id="formCargo" class="form-control-custom" required placeholder="Ej: Desarrollador Senior">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Área *</label>
                                    <select name="id_area" id="formIdArea" class="form-select-custom" required>
                                        <option value="">Seleccione Área</option>
                                        <?php foreach ($areas as $a): ?>
                                            <option value="<?php echo $a['id_area']; ?>"><?php echo htmlspecialchars($a['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group-custom">
                                    <label>Empresa *</label>
                                    <select name="id_empresa" id="formIdEmpresa" class="form-select-custom" required>
                                        <option value="">Seleccione Empresa/Cliente</option>
                                        <?php foreach ($empresas as $e): ?>
                                            <option value="<?php echo $e['id_empresa']; ?>"><?php echo htmlspecialchars($e['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Jefe Inmediato</label>
                                    <select name="id_jefe" id="formIdJefe" class="form-select-custom">
                                        <option value="">Ninguno (Es Administrador / Directivo)</option>
                                        <?php foreach ($jefes as $j): ?>
                                            <option value="<?php echo $j['id_empleado']; ?>"><?php echo htmlspecialchars($j['nombre'] . ' ' . $j['apellido'] . ' - ' . $j['cargo']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group-custom">
                                    <label>Modalidad de Trabajo</label>
                                    <select name="modalidad_trabajo" id="formModalidad" class="form-select-custom">
                                        <option value="Remoto">Remoto</option>
                                        <option value="Presencial">Presencial</option>
                                        <option value="Hibrido">Híbrido</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Rol del Sistema *</label>
                                    <select name="id_rol" id="formIdRol" class="form-select-custom" required>
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?php echo $r['id_rol']; ?>"><?php echo htmlspecialchars($r['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group-custom">
                                    <label>Estado Inicial *</label>
                                    <select name="activo" id="formActivo" class="form-select-custom" required>
                                        <option value="1">Activo (Puede iniciar sesión)</option>
                                        <option value="0">Inactivo (Acceso denegado)</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Fecha de Ingreso *</label>
                                    <input type="date" name="fecha_ingreso" id="formFechaIngreso" class="form-control-custom" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group-custom">
                                    <label id="lblPassword">Contraseña de Acceso *</label>
                                    <input type="password" name="password" id="formPassword" class="form-control-custom" placeholder="Min. 6 caracteres">
                                    <span style="font-size:0.7rem; color:var(--text-muted); display:block; margin-top:4px;" id="helpPassword">Para crear, escriba una contraseña. Para editar, déjelo en blanco si no desea cambiarla.</span>
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer-custom">
                            <button type="button" class="btn-custom btn-custom-secondary" onclick="cerrarModal()">Cancelar</button>
                            <button type="submit" class="btn-custom btn-custom-primary">Guardar Colaborador</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <?php include_once 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Script del Modal y carga de datos de edición -->
    <script>
        const modal = document.getElementById('modalEmpleado');
        const formAccion = document.getElementById('formAccion');
        const modalTitulo = document.getElementById('modalTitulo');
        const formIdEmpleado = document.getElementById('formIdEmpleado');
        const formIdUsuario = document.getElementById('formIdUsuario');
        
        const formNombre = document.getElementById('formNombre');
        const formApellido = document.getElementById('formApellido');
        const formDocumento = document.getElementById('formDocumento');
        const formTelefono = document.getElementById('formTelefono');
        const formCorreo = document.getElementById('formCorreo');
        const formCargo = document.getElementById('formCargo');
        const formIdArea = document.getElementById('formIdArea');
        const formIdEmpresa = document.getElementById('formIdEmpresa');
        const formIdJefe = document.getElementById('formIdJefe');
        const formModalidad = document.getElementById('formModalidad');
        const formIdRol = document.getElementById('formIdRol');
        const formActivo = document.getElementById('formActivo');
        const formFechaIngreso = document.getElementById('formFechaIngreso');
        const formPassword = document.getElementById('formPassword');
        const lblPassword = document.getElementById('lblPassword');

        function abrirModalCrear() {
            // Reiniciar campos
            formAccion.value = 'crear';
            modalTitulo.textContent = 'Registrar Nuevo Colaborador';
            formIdEmpleado.value = '';
            formIdUsuario.value = '';
            
            formNombre.value = '';
            formApellido.value = '';
            formDocumento.value = '';
            formTelefono.value = '';
            formCorreo.value = '';
            formCargo.value = '';
            formIdArea.value = '';
            formIdEmpresa.value = '';
            formIdJefe.value = '';
            formModalidad.value = 'Remoto';
            formIdRol.value = '2'; // Empleado por defecto
            formActivo.value = '1';
            formFechaIngreso.value = new Date().toISOString().split('T')[0];
            formPassword.value = '';
            
            lblPassword.textContent = 'Contraseña de Acceso *';
            formPassword.required = true;

            modal.classList.add('show');
        }

        function abrirModalEditar(emp) {
            formAccion.value = 'editar';
            modalTitulo.textContent = 'Editar Colaborador: ' + emp.nombre + ' ' + emp.apellido;
            formIdEmpleado.value = emp.id_empleado;
            formIdUsuario.value = emp.id_usuario;
            
            formNombre.value = emp.nombre;
            formApellido.value = emp.apellido;
            formDocumento.value = emp.documento;
            formTelefono.value = emp.telefono;
            formCorreo.value = emp.correo;
            formCargo.value = emp.cargo;
            formIdArea.value = emp.id_area;
            formIdEmpresa.value = emp.id_empresa;
            formIdJefe.value = emp.id_jefe || '';
            formModalidad.value = emp.modalidad_trabajo;
            formIdRol.value = emp.id_rol;
            formActivo.value = emp.activo;
            formFechaIngreso.value = emp.fecha_ingreso;
            formPassword.value = '';
            
            lblPassword.textContent = 'Nueva Contraseña (Opcional)';
            formPassword.required = false;

            modal.classList.add('show');
        }

        function cerrarModal() {
            modal.classList.remove('show');
        }

        // Cerrar modal al hacer clic afuera
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModal();
            }
        });
    </script>
</body>

</html>
