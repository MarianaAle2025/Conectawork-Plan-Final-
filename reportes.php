<?php
require_once 'includes/session.php';
require_once 'app/config/conexion.php';

$mensaje = '';
$tipo_mensaje = '';

$id_usuario = $_SESSION['id_usuario'];
$id_empleado = $_SESSION['id_empleado'];
$id_rol = $_SESSION['id_rol'];

// Función para formatear minutos en "X horas Y minutos"
function formatearMinutos($minutos) {
    if (empty($minutos) || $minutos <= 0) {
        return "0 min";
    }
    $horas = floor($minutos / 60);
    $mins = $minutos % 60;
    
    $out = "";
    if ($horas > 0) {
        $out .= $horas . " " . ($horas == 1 ? "hora" : "horas");
    }
    if ($mins > 0) {
        if ($horas > 0) $out .= " ";
        $out .= $mins . " " . ($mins == 1 ? "minuto" : "minutos");
    }
    return $out;
}

// Obtener datos del empleado para rellenar jefe e inicializar formulario
$jefe_inmediato_id = null;
$jefe_inmediato_nombre = "Ninguno";
if ($id_empleado) {
    $stmt = $conexion->prepare("
        SELECT e.id_jefe, CONCAT(j.nombre, ' ', j.apellido) AS jefe_nombre 
        FROM empleado e 
        LEFT JOIN empleado j ON e.id_jefe = j.id_empleado 
        WHERE e.id_empleado = ?
    ");
    $stmt->bind_param("i", $id_empleado);
    $stmt->execute();
    $res_emp = $stmt->get_result();
    if ($res_emp && $res_emp->num_rows > 0) {
        $row_emp = $res_emp->fetch_assoc();
        $jefe_inmediato_id = $row_emp['id_jefe'];
        $jefe_inmediato_nombre = $row_emp['jefe_nombre'] ?? "Ninguno";
    }
    $stmt->close();
}

// ========================================================
// PROCESAMIENTO DE ACCIONES (POST)
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // CREAR REPORTE
    if ($accion === 'crear') {
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $hora_inicio = $_POST['hora_inicio'] ?? '';
        $hora_fin = $_POST['hora_fin'] ?? '';
        $id_actividad = intval($_POST['id_actividad'] ?? 0);
        $id_proyecto = intval($_POST['id_proyecto'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'Finalizado';

        if (!empty($hora_inicio) && !empty($hora_fin) && $id_actividad > 0 && $id_proyecto > 0 && !empty($descripcion)) {
            
            // Validar que la hora fin sea mayor que hora inicio
            $t_inicio = strtotime($hora_inicio);
            $t_fin = strtotime($hora_fin);

            if ($t_fin <= $t_inicio) {
                $mensaje = "La hora de finalización debe ser posterior a la hora de inicio.";
                $tipo_mensaje = "danger";
            } else {
                // Calcular tiempo total en minutos
                $tiempo_total_minutos = round(($t_fin - $t_inicio) / 60);

                // Obtener empresa asociada al proyecto para asegurar consistencia
                $stmt = $conexion->prepare("SELECT id_empresa FROM proyectos WHERE id_proyecto = ?");
                $stmt->bind_param("i", $id_proyecto);
                $stmt->execute();
                $res_proj = $stmt->get_result();
                $id_empresa = 0;
                if ($res_proj && $res_proj->num_rows > 0) {
                    $id_empresa = $res_proj->fetch_assoc()['id_empresa'];
                }
                $stmt->close();

                if ($id_empresa > 0 && $id_empleado > 0) {
                    $stmt = $conexion->prepare("
                        INSERT INTO reportes_actividades (id_empleado, fecha, hora_inicio, hora_fin, tiempo_total_minutos, id_actividad, id_proyecto, id_empresa, id_jefe, descripcion, estado)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("isssiiiiiss", $id_empleado, $fecha, $hora_inicio, $hora_fin, $tiempo_total_minutos, $id_actividad, $id_proyecto, $id_empresa, $jefe_inmediato_id, $descripcion, $estado);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Actividad registrada con éxito.";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al guardar el reporte: " . $conexion->error;
                        $tipo_mensaje = "danger";
                    }
                    $stmt->close();
                } else {
                    $mensaje = "No se pudo determinar el cliente o el empleado asociado.";
                    $tipo_mensaje = "danger";
                }
            }
        } else {
            $mensaje = "Por favor complete todos los campos obligatorios.";
            $tipo_mensaje = "danger";
        }
    }

    // EDITAR REPORTE
    elseif ($accion === 'editar') {
        $id_reporte = intval($_POST['id_reporte'] ?? 0);
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $hora_inicio = $_POST['hora_inicio'] ?? '';
        $hora_fin = $_POST['hora_fin'] ?? '';
        $id_actividad = intval($_POST['id_actividad'] ?? 0);
        $id_proyecto = intval($_POST['id_proyecto'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'Finalizado';

        if ($id_reporte > 0 && !empty($hora_inicio) && !empty($hora_fin) && $id_actividad > 0 && $id_proyecto > 0 && !empty($descripcion)) {
            
            // Validar que la hora fin sea mayor que hora inicio
            $t_inicio = strtotime($hora_inicio);
            $t_fin = strtotime($hora_fin);

            if ($t_fin <= $t_inicio) {
                $mensaje = "La hora de finalización debe ser posterior a la hora de inicio.";
                $tipo_mensaje = "danger";
            } else {
                $tiempo_total_minutos = round(($t_fin - $t_inicio) / 60);

                // Obtener empresa asociada al proyecto
                $stmt = $conexion->prepare("SELECT id_empresa FROM proyectos WHERE id_proyecto = ?");
                $stmt->bind_param("i", $id_proyecto);
                $stmt->execute();
                $res_proj = $stmt->get_result();
                $id_empresa = 0;
                if ($res_proj && $res_proj->num_rows > 0) {
                    $id_empresa = $res_proj->fetch_assoc()['id_empresa'];
                }
                $stmt->close();

                // Verificar propiedad si es Rol Empleado
                $autorizado = true;
                if ($id_rol != 1) {
                    $stmt = $conexion->prepare("SELECT id_empleado FROM reportes_actividades WHERE id_reporte = ?");
                    $stmt->bind_param("i", $id_reporte);
                    $stmt->execute();
                    $res_rep = $stmt->get_result();
                    if ($res_rep && $res_rep->num_rows > 0) {
                        $rep_emp = $res_rep->fetch_assoc()['id_empleado'];
                        if ($rep_emp != $id_empleado) {
                            $autorizado = false;
                        }
                    } else {
                        $autorizado = false;
                    }
                    $stmt->close();
                }

                if ($autorizado && $id_empresa > 0) {
                    $stmt = $conexion->prepare("
                        UPDATE reportes_actividades 
                        SET fecha = ?, hora_inicio = ?, hora_fin = ?, tiempo_total_minutos = ?, id_actividad = ?, id_proyecto = ?, id_empresa = ?, descripcion = ?, estado = ?
                        WHERE id_reporte = ?
                    ");
                    $stmt->bind_param("sssiisissi", $fecha, $hora_inicio, $hora_fin, $tiempo_total_minutos, $id_actividad, $id_proyecto, $id_empresa, $descripcion, $estado, $id_reporte);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Reporte de actividad actualizado.";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al actualizar el reporte: " . $conexion->error;
                        $tipo_mensaje = "danger";
                    }
                    $stmt->close();
                } else {
                    $mensaje = "No tienes permiso para editar este reporte.";
                    $tipo_mensaje = "danger";
                }
            }
        } else {
            $mensaje = "Por favor complete todos los campos obligatorios.";
            $tipo_mensaje = "danger";
        }
    }

    // ELIMINAR REPORTE
    elseif ($accion === 'eliminar') {
        $id_reporte = intval($_POST['id_reporte'] ?? 0);

        if ($id_reporte > 0) {
            $autorizado = true;
            // Verificar propiedad si es Rol Empleado
            if ($id_rol != 1) {
                $stmt = $conexion->prepare("SELECT id_empleado FROM reportes_actividades WHERE id_reporte = ?");
                $stmt->bind_param("i", $id_reporte);
                $stmt->execute();
                $res_rep = $stmt->get_result();
                if ($res_rep && $res_rep->num_rows > 0) {
                    $rep_emp = $res_rep->fetch_assoc()['id_empleado'];
                    if ($rep_emp != $id_empleado) {
                        $autorizado = false;
                    }
                } else {
                    $autorizado = false;
                }
                $stmt->close();
            }

            if ($autorizado) {
                $stmt = $conexion->prepare("DELETE FROM reportes_actividades WHERE id_reporte = ?");
                $stmt->bind_param("i", $id_reporte);
                if ($stmt->execute()) {
                    $mensaje = "Reporte de actividad eliminado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al eliminar el reporte: " . $conexion->error;
                    $tipo_mensaje = "danger";
                }
                $stmt->close();
            } else {
                $mensaje = "No tienes permiso para eliminar este reporte.";
                $tipo_mensaje = "danger";
            }
        }
    }
}

// ========================================================
// OBTENER COMBOS Y CATÁLOGOS PARA FORMULARIOS Y FILTROS
// ========================================================
$actividades = [];
$res_act = $conexion->query("SELECT * FROM actividades ORDER BY nombre ASC");
while ($row = $res_act->fetch_assoc()) {
    $actividades[] = $row;
}

$proyectos = [];
$res_pro = $conexion->query("
    SELECT p.*, e.nombre AS empresa_nombre 
    FROM proyectos p 
    INNER JOIN empresas e ON p.id_empresa = e.id_empresa 
    ORDER BY p.nombre ASC
");
while ($row = $res_pro->fetch_assoc()) {
    $proyectos[] = $row;
}

$empresas = [];
$res_emp = $conexion->query("SELECT * FROM empresas ORDER BY nombre ASC");
while ($row = $res_emp->fetch_assoc()) {
    $empresas[] = $row;
}

$empleados = [];
if ($id_rol == 1) {
    $res_col = $conexion->query("SELECT id_empleado, nombre, apellido, cargo FROM empleado ORDER BY nombre ASC");
    while ($row = $res_col->fetch_assoc()) {
        $empleados[] = $row;
    }
}

// ========================================================
// FILTROS Y BÚSQUEDA (Para administrador, o propio para empleado)
// ========================================================
$filtro_empleado = ($id_rol == 1) ? intval($_GET['f_empleado'] ?? 0) : $id_empleado;
$filtro_proyecto = intval($_GET['f_proyecto'] ?? 0);
$filtro_empresa = intval($_GET['f_empresa'] ?? 0);
$filtro_actividad = intval($_GET['f_actividad'] ?? 0);
$filtro_fecha = $_GET['f_fecha'] ?? '';
$filtro_rango_inicio = $_GET['f_rango_inicio'] ?? '';
$filtro_rango_fin = $_GET['f_rango_fin'] ?? '';

$sql_cond = "";
$bind_types = "";
$bind_args = [];

if ($filtro_empleado > 0) {
    $sql_cond .= " AND r.id_empleado = ?";
    $bind_types .= "i";
    $bind_args[] = $filtro_empleado;
}
if ($filtro_proyecto > 0) {
    $sql_cond .= " AND r.id_proyecto = ?";
    $bind_types .= "i";
    $bind_args[] = $filtro_proyecto;
}
if ($filtro_empresa > 0) {
    $sql_cond .= " AND r.id_empresa = ?";
    $bind_types .= "i";
    $bind_args[] = $filtro_empresa;
}
if ($filtro_actividad > 0) {
    $sql_cond .= " AND r.id_actividad = ?";
    $bind_types .= "i";
    $bind_args[] = $filtro_actividad;
}
if (!empty($filtro_fecha)) {
    $sql_cond .= " AND r.fecha = ?";
    $bind_types .= "s";
    $bind_args[] = $filtro_fecha;
}
if (!empty($filtro_rango_inicio) && !empty($filtro_rango_fin)) {
    $sql_cond .= " AND r.fecha BETWEEN ? AND ?";
    $bind_types .= "ss";
    $bind_args[] = $filtro_rango_inicio;
    $bind_args[] = $filtro_rango_fin;
}

// 1. Obtener la lista filtrada de reportes
$query_reportes = "
    SELECT r.*, e.nombre AS empleado_nombre, e.apellido AS empleado_apellido, e.cargo AS empleado_cargo,
           a.nombre AS actividad_nombre, p.nombre AS proyecto_nombre, emp.nombre AS empresa_nombre,
           CONCAT(j.nombre, ' ', j.apellido) AS jefe_nombre
    FROM reportes_actividades r
    INNER JOIN empleado e ON r.id_empleado = e.id_empleado
    INNER JOIN actividades a ON r.id_actividad = a.id_actividad
    INNER JOIN proyectos p ON r.id_proyecto = p.id_proyecto
    INNER JOIN empresas emp ON r.id_empresa = emp.id_empresa
    LEFT JOIN empleado j ON r.id_jefe = j.id_empleado
    WHERE 1=1 $sql_cond
    ORDER BY r.fecha DESC, r.hora_inicio DESC
";

$stmt_rep = $conexion->prepare($query_reportes);
if (!empty($bind_types)) {
    $stmt_rep->bind_param($bind_types, ...$bind_args);
}
$stmt_rep->execute();
$result_reportes = $stmt_rep->get_result();

// 2. Cálculos estadísticos (totales filtrados)
// Total Horas General
$total_minutos = 0;
$total_minutos_reuniones = 0;
$total_minutos_desarrollo = 0;
$total_minutos_capacitaciones = 0;

$reportes_array = [];
while ($row = $result_reportes->fetch_assoc()) {
    $reportes_array[] = $row;
    $min = $row['tiempo_total_minutos'];
    $total_minutos += $min;
    
    // Agregados según tipo de actividad
    // Comparar con el nombre de la actividad (o IDs si fuesen estáticos, pero comparar por nombre es seguro en este caso)
    $act_name = strtolower($row['actividad_nombre']);
    if (strpos($act_name, 'reunión') !== false || strpos($act_name, 'reunion') !== false) {
        $total_minutos_reuniones += $min;
    } elseif (strpos($act_name, 'desarrollo') !== false) {
        $total_minutos_desarrollo += $min;
    } elseif (strpos($act_name, 'capacitación') !== false || strpos($act_name, 'capacitacion') !== false) {
        $total_minutos_capacitaciones += $min;
    }
}

// Convertir para presentación
$total_horas_texto = formatearMinutos($total_minutos);
$total_reuniones_texto = formatearMinutos($total_minutos_reuniones);
$total_desarrollo_texto = formatearMinutos($total_minutos_desarrollo);
$total_capacitaciones_texto = formatearMinutos($total_minutos_capacitaciones);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConectaWork | Reportes de Actividades</title>

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
                        <h2 style="font-size: 1.6rem; font-weight: 700;">Reporte de Actividades</h2>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">
                            <?php if ($id_rol == 1): ?>
                                Control general de tiempos de trabajo y actividades registradas por los colaboradores.
                            <?php else: ?>
                                Registra tu jornada de trabajo y mantén un historial de tus actividades diarias.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($id_rol != 1): ?>
                        <button class="btn-custom btn-custom-primary" onclick="abrirModalCrearReporte()">
                            <i class="fa-solid fa-clock"></i> Registrar Actividad
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Mensajes de feedback -->
                <?php if (!empty($mensaje)): ?>
                    <div class="alert-custom alert-custom-<?php echo $tipo_mensaje; ?>">
                        <i class="fa-solid <?php echo ($tipo_mensaje === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                        <span><?php echo htmlspecialchars($mensaje); ?></span>
                    </div>
                <?php endif; ?>

                <!-- ========================================================
                   TARJETAS DE MÉTRICAS AGREGADAS (Solo Administrador, o resumen personal)
                   ======================================================== -->
                <section class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 8px;">
                    <div class="metric-card" style="padding: 14px 18px;">
                        <div class="metric-info">
                            <span class="metric-label">Horas Totales</span>
                            <span class="metric-value" style="font-size: 1.15rem; margin-top: 4px;"><?php echo $total_horas_texto; ?></span>
                            <span style="font-size:0.68rem; color:var(--text-muted);">En el filtro actual</span>
                        </div>
                        <div class="metric-icon-box" style="background-color: var(--primary-light); color: var(--primary); width: 36px; height: 36px; border-radius: var(--radius-sm); display:flex; align-items:center; justify-content:center;">
                            <i class="fa-solid fa-business-time"></i>
                        </div>
                    </div>
                    
                    <div class="metric-card" style="padding: 14px 18px;">
                        <div class="metric-info">
                            <span class="metric-label">Desarrollo</span>
                            <span class="metric-value" style="font-size: 1.15rem; margin-top: 4px;"><?php echo $total_desarrollo_texto; ?></span>
                            <span style="font-size:0.68rem; color:var(--text-muted);">Dedicado a programar</span>
                        </div>
                        <div class="metric-icon-box" style="background-color: var(--accent-light); color: var(--accent); width: 36px; height: 36px; border-radius: var(--radius-sm); display:flex; align-items:center; justify-content:center;">
                            <i class="fa-solid fa-code"></i>
                        </div>
                    </div>

                    <div class="metric-card" style="padding: 14px 18px;">
                        <div class="metric-info">
                            <span class="metric-label">Reuniones</span>
                            <span class="metric-value" style="font-size: 1.15rem; margin-top: 4px;"><?php echo $total_reuniones_texto; ?></span>
                            <span style="font-size:0.68rem; color:var(--text-muted);">Alineaciones y llamadas</span>
                        </div>
                        <div class="metric-icon-box" style="background-color: var(--warning-light); color: var(--warning); width: 36px; height: 36px; border-radius: var(--radius-sm); display:flex; align-items:center; justify-content:center;">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>

                    <div class="metric-card" style="padding: 14px 18px;">
                        <div class="metric-info">
                            <span class="metric-label">Capacitaciones</span>
                            <span class="metric-value" style="font-size: 1.15rem; margin-top: 4px;"><?php echo $total_capacitaciones_texto; ?></span>
                            <span style="font-size:0.68rem; color:var(--text-muted);">Estudio y autoformación</span>
                        </div>
                        <div class="metric-icon-box" style="background-color: var(--success-light); color: var(--success); width: 36px; height: 36px; border-radius: var(--radius-sm); display:flex; align-items:center; justify-content:center;">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                    </div>
                </section>

                <!-- ========================================================
                   FILTROS DE BÚSQUEDA AVANZADOS
                   ======================================================== -->
                <div class="content-card">
                    <h3 style="font-size: 0.9rem; font-weight:600; text-transform:uppercase; margin-bottom:15px; color:var(--text-muted); display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-filter" style="color:var(--accent);"></i> Filtrar Actividades
                    </h3>
                    <form method="GET" action="reportes.php">
                        <div class="filter-grid" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));">
                            
                            <?php if ($id_rol == 1): ?>
                                <div class="form-group-custom" style="margin-bottom:0;">
                                    <label>Colaborador</label>
                                    <select name="f_empleado" class="form-select-custom">
                                        <option value="0">Todos los empleados</option>
                                        <?php foreach ($empleados as $e): ?>
                                            <option value="<?php echo $e['id_empleado']; ?>" <?php echo ($filtro_empleado == $e['id_empleado']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($e['nombre'] . ' ' . $e['apellido']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="form-group-custom" style="margin-bottom:0;">
                                <label>Proyecto</label>
                                <select name="f_proyecto" class="form-select-custom">
                                    <option value="0">Todos los proyectos</option>
                                    <?php foreach ($proyectos as $p): ?>
                                        <option value="<?php echo $p['id_proyecto']; ?>" <?php echo ($filtro_proyecto == $p['id_proyecto']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group-custom" style="margin-bottom:0;">
                                <label>Cliente / Empresa</label>
                                <select name="f_empresa" class="form-select-custom">
                                    <option value="0">Todos los clientes</option>
                                    <?php foreach ($empresas as $emp): ?>
                                        <option value="<?php echo $emp['id_empresa']; ?>" <?php echo ($filtro_empresa == $emp['id_empresa']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emp['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group-custom" style="margin-bottom:0;">
                                <label>Tipo de Actividad</label>
                                <select name="f_actividad" class="form-select-custom">
                                    <option value="0">Todas las actividades</option>
                                    <?php foreach ($actividades as $act): ?>
                                        <option value="<?php echo $act['id_actividad']; ?>" <?php echo ($filtro_actividad == $act['id_actividad']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($act['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group-custom" style="margin-bottom:0;">
                                <label>Fecha Única</label>
                                <input type="date" name="f_fecha" class="form-control-custom" value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                            </div>

                            <div class="form-group-custom" style="margin-bottom:0;">
                                <label>Rango (Desde)</label>
                                <input type="date" name="f_rango_inicio" class="form-control-custom" value="<?php echo htmlspecialchars($filtro_rango_inicio); ?>">
                            </div>

                            <div class="form-group-custom" style="margin-bottom:0;">
                                <label>Rango (Hasta)</label>
                                <input type="date" name="f_rango_fin" class="form-control-custom" value="<?php echo htmlspecialchars($filtro_rango_fin); ?>">
                            </div>

                            <div style="display: flex; gap:8px;">
                                <button type="submit" class="btn-custom btn-custom-accent" style="flex:1; height: 42px;">
                                    Aplicar
                                </button>
                                <a href="reportes.php" class="btn-custom btn-custom-secondary" style="height: 42px; display:flex; align-items:center;" title="Limpiar Filtros">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </a>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- ========================================================
                   TABLA DE REPORTES DE ACTIVIDADES
                   ======================================================== -->
                <div class="content-card" style="padding: 16px 0;">
                    <div class="table-responsive-custom">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <?php if ($id_rol == 1): ?>
                                        <th>Colaborador</th>
                                    <?php endif; ?>
                                    <th>Fecha</th>
                                    <th>Horario</th>
                                    <th>Tiempo Dedicado</th>
                                    <th>Proyecto</th>
                                    <th>Cliente / Empresa</th>
                                    <th>Actividad</th>
                                    <th>Descripción Detallada</th>
                                    <th>Jefe Inmediato</th>
                                    <th>Estado</th>
                                    <th style="text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($reportes_array) > 0): ?>
                                    <?php foreach ($reportes_array as $rep): ?>
                                        <tr>
                                            <?php if ($id_rol == 1): ?>
                                                <td>
                                                    <div style="font-weight:600; font-size:0.82rem;"><?php echo htmlspecialchars($rep['empleado_nombre'] . ' ' . $rep['empleado_apellido']); ?></div>
                                                    <div style="font-size:0.7rem; color:var(--text-muted);"><?php echo htmlspecialchars($rep['empleado_cargo']); ?></div>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <span style="font-weight: 500; font-size: 0.8rem;">
                                                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($rep['fecha']))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size: 0.78rem; color: var(--text-muted); font-family: monospace;">
                                                    <?php echo htmlspecialchars(date('g:i A', strtotime($rep['hora_inicio']))) . ' - ' . htmlspecialchars(date('g:i A', strtotime($rep['hora_fin']))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-weight: 600; color: var(--text); display:inline-flex; align-items:center; gap:4px;">
                                                    <i class="fa-regular fa-clock" style="color:var(--accent); font-size:0.75rem;"></i>
                                                    <?php echo formatearMinutos($rep['tiempo_total_minutos']); ?>
                                                </span>
                                            </td>
                                            <td><span style="font-weight: 500;"><?php echo htmlspecialchars($rep['proyecto_nombre']); ?></span></td>
                                            <td><span style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($rep['empresa_nombre']); ?></span></td>
                                            <td>
                                                <span class="badge-custom badge-custom-primary">
                                                    <?php echo htmlspecialchars($rep['actividad_nombre']); ?>
                                                </span>
                                            </td>
                                            <td style="max-width: 250px; font-size: 0.78rem; line-height: 1.4; color: var(--text-muted);">
                                                <?php echo nl2br(htmlspecialchars($rep['descripcion'])); ?>
                                            </td>
                                            <td>
                                                <span style="font-size:0.78rem; color:var(--text-muted);">
                                                    <?php echo htmlspecialchars($rep['jefe_nombre'] ?? 'Ninguno'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge-custom <?php echo ($rep['estado'] === 'Finalizado') ? 'badge-custom-success' : 'badge-custom-warning'; ?>">
                                                    <?php echo htmlspecialchars($rep['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 8px; justify-content: center;">
                                                    <!-- Editar (Sólo permitido si es Admin, o si es Empleado y es su propio reporte) -->
                                                    <?php if ($id_rol == 1 || $rep['id_empleado'] == $id_empleado): ?>
                                                        <button class="btn-custom btn-custom-secondary btn-custom-sm" onclick="abrirModalEditarReporte(<?php echo htmlspecialchars(json_encode($rep)); ?>)" title="Editar Actividad">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <form method="POST" action="reportes.php" onsubmit="return confirm('¿Estás seguro de eliminar este reporte de actividad?')" style="display:inline;">
                                                            <input type="hidden" name="accion" value="eliminar">
                                                            <input type="hidden" name="id_reporte" value="<?php echo $rep['id_reporte']; ?>">
                                                            <button type="submit" class="btn-custom btn-custom-danger btn-custom-sm" title="Eliminar Actividad">
                                                                <i class="fa-solid fa-trash-can"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="font-size:0.7rem; color:var(--text-muted);">Lectura</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo ($id_rol == 1) ? '11' : '10'; ?>" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                            <i class="fa-regular fa-clock" style="font-size: 2rem; display:block; margin-bottom:10px;"></i>
                                            No se han registrado reportes de actividades bajo los filtros indicados.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>

            <!-- ========================================================
               MODAL CREAR / EDITAR REPORTE DE ACTIVIDADES
               ======================================================== -->
            <div class="modal-custom" id="modalReporte">
                <div class="modal-content-custom">
                    <form method="POST" action="reportes.php" id="formReporte">
                        <input type="hidden" name="accion" id="repAccion" value="crear">
                        <input type="hidden" name="id_reporte" id="repIdReporte" value="">

                        <div class="modal-header-custom">
                            <h3 id="repModalTitulo">Registrar Reporte de Actividad</h3>
                            <span class="modal-close-custom" onclick="cerrarModalReporte()">&times;</span>
                        </div>
                        <div class="modal-body-custom">
                            
                            <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Jefe Inmediato (Automático)</label>
                                    <input type="text" class="form-control-custom" value="<?php echo htmlspecialchars($jefe_inmediato_nombre); ?>" disabled style="background-color:rgba(255,255,255,0.01); color:var(--text-muted); border-color:var(--border);">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Fecha *</label>
                                    <input type="date" name="fecha" id="repFecha" class="form-control-custom" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Hora de Inicio *</label>
                                    <input type="time" name="hora_inicio" id="repHoraInicio" class="form-control-custom" required onchange="calcularTiempoTotalJS()">
                                </div>
                                <div class="form-group-custom">
                                    <label>Hora de Fin *</label>
                                    <input type="time" name="hora_fin" id="repHoraFin" class="form-control-custom" required onchange="calcularTiempoTotalJS()">
                                </div>
                            </div>

                            <div class="form-group-custom" style="background-color:rgba(139, 92, 246, 0.04); border:1px solid rgba(139,92,246,0.1); padding:10px 16px; border-radius:var(--radius-md); display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:600; letter-spacing:0.5px;">Tiempo Total Calculado</span>
                                <span id="repTiempoCalculado" style="font-weight:700; color:var(--accent); font-size:0.9rem;">0 minutos</span>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Proyecto *</label>
                                    <select name="id_proyecto" id="repIdProyecto" class="form-select-custom" required onchange="autoSeleccionarCliente()">
                                        <option value="">Seleccione Proyecto</option>
                                        <?php foreach ($proyectos as $p): ?>
                                            <option value="<?php echo $p['id_proyecto']; ?>" data-empresa-id="<?php echo $p['id_empresa']; ?>" data-empresa-nombre="<?php echo htmlspecialchars($p['empresa_nombre']); ?>">
                                                <?php echo htmlspecialchars($p['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group-custom">
                                    <label>Cliente / Empresa (Auto-asignado)</label>
                                    <input type="text" id="repClienteVisible" class="form-control-custom" disabled value="Seleccione un proyecto..." style="background-color:rgba(255,255,255,0.01); color:var(--text-muted); border-color:var(--border);">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group-custom">
                                    <label>Tipo de Actividad *</label>
                                    <select name="id_actividad" id="repIdActividad" class="form-select-custom" required>
                                        <option value="">Seleccione Actividad</option>
                                        <?php foreach ($actividades as $act): ?>
                                            <option value="<?php echo $act['id_actividad']; ?>"><?php echo htmlspecialchars($act['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group-custom">
                                    <label>Estado de Actividad *</label>
                                    <select name="estado" id="repEstado" class="form-select-custom" required>
                                        <option value="Finalizado">Finalizado</option>
                                        <option value="En proceso">En proceso</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group-custom">
                                <label>Descripción detallada de la Actividad *</label>
                                <textarea name="descripcion" id="repDescripcion" class="form-control-custom" rows="4" required placeholder="Escribe detalles del desarrollo o tarea realizada..." style="resize:vertical;"></textarea>
                            </div>

                        </div>
                        <div class="modal-footer-custom">
                            <button type="button" class="btn-custom btn-custom-secondary" onclick="cerrarModalReporte()">Cancelar</button>
                            <button type="submit" class="btn-custom btn-custom-primary">Guardar Reporte</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <?php include_once 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Script del Modal y Autocalculos -->
    <script>
        const modalReporte = document.getElementById('modalReporte');
        const repAccion = document.getElementById('repAccion');
        const repModalTitulo = document.getElementById('repModalTitulo');
        const repIdReporte = document.getElementById('repIdReporte');

        const repFecha = document.getElementById('repFecha');
        const repHoraInicio = document.getElementById('repHoraInicio');
        const repHoraFin = document.getElementById('repHoraFin');
        const repTiempoCalculado = document.getElementById('repTiempoCalculado');
        const repIdProyecto = document.getElementById('repIdProyecto');
        const repClienteVisible = document.getElementById('repClienteVisible');
        const repIdActividad = document.getElementById('repIdActividad');
        const repEstado = document.getElementById('repEstado');
        const repDescripcion = document.getElementById('repDescripcion');

        // Formatea minutos en horas y minutos para la UI
        function formatearMinutosJS(minutos) {
            if (minutos <= 0) return "0 minutos";
            const horas = Math.floor(minutos / 60);
            const mins = minutos % 60;
            
            let out = "";
            if (horas > 0) {
                out += horas + " " + (horas === 1 ? "hora" : "horas");
            }
            if (mins > 0) {
                if (horas > 0) out += " ";
                out += mins + " " + (mins === 1 ? "minuto" : "minutos");
            }
            return out;
        }

        // Calcula la diferencia horaria automáticamente
        function calcularTiempoTotalJS() {
            const hInicio = repHoraInicio.value;
            const hFin = repHoraFin.value;

            if (hInicio && hFin) {
                const dummyDate = '2026-07-07 '; // Usar fecha fija ficticia para calcular diferencias horarias
                const tInicio = new Date(dummyDate + hInicio);
                const tFin = new Date(dummyDate + hFin);

                if (tFin > tInicio) {
                    const diffMs = tFin - tInicio;
                    const diffMin = Math.round(diffMs / 60000);
                    repTiempoCalculado.textContent = formatearMinutosJS(diffMin);
                } else {
                    repTiempoCalculado.textContent = "La hora fin debe ser posterior";
                }
            } else {
                repTiempoCalculado.textContent = "0 minutos";
            }
        }

        // Auto-selecciona el cliente basado en el proyecto seleccionado
        function autoSeleccionarCliente() {
            const selectedOption = repIdProyecto.options[repIdProyecto.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const clienteNombre = selectedOption.getAttribute('data-empresa-nombre');
                repClienteVisible.value = clienteNombre;
            } else {
                repClienteVisible.value = "Seleccione un proyecto...";
            }
        }

        function abrirModalCrearReporte() {
            repAccion.value = 'crear';
            repModalTitulo.textContent = 'Registrar Reporte de Actividad';
            repIdReporte.value = '';
            
            repFecha.value = new Date().toISOString().split('T')[0];
            repHoraInicio.value = '08:00';
            repHoraFin.value = '10:00';
            repIdProyecto.value = '';
            repClienteVisible.value = 'Seleccione un proyecto...';
            repIdActividad.value = '';
            repEstado.value = 'Finalizado';
            repDescripcion.value = '';
            
            calcularTiempoTotalJS();
            
            modalReporte.classList.add('show');
        }

        function abrirModalEditarReporte(rep) {
            repAccion.value = 'editar';
            repModalTitulo.textContent = 'Editar Reporte de Actividad';
            repIdReporte.value = rep.id_reporte;
            
            repFecha.value = rep.fecha;
            repHoraInicio.value = rep.hora_inicio.substring(0, 5);
            repHoraFin.value = rep.hora_fin.substring(0, 5);
            repIdProyecto.value = rep.id_proyecto;
            repIdActividad.value = rep.id_actividad;
            repEstado.value = rep.estado;
            repDescripcion.value = rep.descripcion;
            
            autoSeleccionarCliente();
            calcularTiempoTotalJS();

            modalReporte.classList.add('show');
        }

        function cerrarModalReporte() {
            modalReporte.classList.remove('show');
        }

        // Cerrar modal al hacer clic afuera
        window.addEventListener('click', function(e) {
            if (e.target === modalReporte) {
                cerrarModalReporte();
            }
        });
    </script>
</body>

</html>
