<?php
require_once 'includes/session.php';
require_once 'app/config/conexion.php';

$id_rol = $_SESSION['id_rol'];
$id_empleado = $_SESSION['id_empleado'];
$nombre_usuario = $_SESSION['nombre'];

// Helper para convertir minutos a horas legibles en el Dashboard
function minutosAHorasDashboard($minutos) {
    if (empty($minutos) || $minutos <= 0) {
        return "0h";
    }
    $horas = floor($minutos / 60);
    $mins = $minutos % 60;
    if ($mins == 0) {
        return $horas . "h";
    }
    return $horas . "h " . $mins . "m";
}

// ========================================================
// CONSULTAS PARA ROL ADMINISTRADOR (id_rol = 1)
// ========================================================
if ($id_rol == 1) {
    // 1. Total Empleados
    $res = $conexion->query("SELECT COUNT(*) FROM empleado");
    $total_empleados = $res ? $res->fetch_row()[0] : 0;

    // 2. Empleados Activos
    $res = $conexion->query("SELECT COUNT(*) FROM usuario WHERE activo = 1");
    $empleados_activos = $res ? $res->fetch_row()[0] : 0;

    // 3. Reportes realizados hoy
    $res = $conexion->query("SELECT COUNT(*) FROM reportes_actividades WHERE fecha = CURDATE()");
    $reportes_hoy = $res ? $res->fetch_row()[0] : 0;

    // 4. Horas trabajadas hoy (en minutos)
    $res = $conexion->query("SELECT SUM(tiempo_total_minutos) FROM reportes_actividades WHERE fecha = CURDATE()");
    $minutos_hoy = $res ? $res->fetch_row()[0] : 0;
    $horas_hoy_texto = minutosAHorasDashboard($minutos_hoy);

    // 5. Horas trabajadas esta semana (en minutos)
    $res = $conexion->query("SELECT SUM(tiempo_total_minutos) FROM reportes_actividades WHERE YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)");
    $minutos_semana = $res ? $res->fetch_row()[0] : 0;
    $horas_semana_texto = minutosAHorasDashboard($minutos_semana);

    // 6. Horas trabajadas este mes (en minutos)
    $res = $conexion->query("SELECT SUM(tiempo_total_minutos) FROM reportes_actividades WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())");
    $minutos_mes = $res ? $res->fetch_row()[0] : 0;
    $horas_mes_texto = minutosAHorasDashboard($minutos_mes);

    // 7. Actividad más frecuente
    $res = $conexion->query("
        SELECT a.nombre, COUNT(*) as cnt 
        FROM reportes_actividades r 
        INNER JOIN actividades a ON r.id_actividad = a.id_actividad 
        GROUP BY r.id_actividad 
        ORDER BY cnt DESC 
        LIMIT 1
    ");
    $actividad_frecuente = ($res && $row = $res->fetch_assoc()) ? $row['nombre'] : "Ninguna";

    // 8. Proyecto con más horas
    $res = $conexion->query("
        SELECT p.nombre, SUM(r.tiempo_total_minutos) as total_min 
        FROM reportes_actividades r 
        INNER JOIN proyectos p ON r.id_proyecto = p.id_proyecto 
        GROUP BY r.id_proyecto 
        ORDER BY total_min DESC 
        LIMIT 1
    ");
    $proyecto_mas_horas = ($res && $row = $res->fetch_assoc()) ? $row['nombre'] . " (" . minutosAHorasDashboard($row['total_min']) . ")" : "Ninguno";

    // 9. Empleado con más horas registradas
    $res = $conexion->query("
        SELECT CONCAT(e.nombre, ' ', e.apellido) as emp_nombre, SUM(r.tiempo_total_minutos) as total_min 
        FROM reportes_actividades r 
        INNER JOIN empleado e ON r.id_empleado = e.id_empleado 
        GROUP BY r.id_empleado 
        ORDER BY total_min DESC 
        LIMIT 1
    ");
    $empleado_mas_horas = ($res && $row = $res->fetch_assoc()) ? $row['emp_nombre'] . " (" . minutosAHorasDashboard($row['total_min']) . ")" : "Ninguno";

    // 10. Datos para Gráfico 1: Horas por Empleado
    $chart_emp_labels = [];
    $chart_emp_data = [];
    $res_chart1 = $conexion->query("
        SELECT CONCAT(e.nombre, ' ', e.apellido) as emp_nombre, ROUND(SUM(r.tiempo_total_minutos)/60, 1) as total_horas 
        FROM reportes_actividades r 
        INNER JOIN empleado e ON r.id_empleado = e.id_empleado 
        GROUP BY r.id_empleado 
        ORDER BY total_horas DESC
    ");
    while ($res_chart1 && $row = $res_chart1->fetch_assoc()) {
        $chart_emp_labels[] = $row['emp_nombre'];
        $chart_emp_data[] = $row['total_horas'];
    }

    // 11. Datos para Gráfico 2: Horas por Actividad
    $chart_act_labels = [];
    $chart_act_data = [];
    $res_chart2 = $conexion->query("
        SELECT a.nombre as act_nombre, ROUND(SUM(r.tiempo_total_minutos)/60, 1) as total_horas 
        FROM reportes_actividades r 
        INNER JOIN actividades a ON r.id_actividad = a.id_actividad 
        GROUP BY r.id_actividad 
        ORDER BY total_horas DESC
    ");
    while ($res_chart2 && $row = $res_chart2->fetch_assoc()) {
        $chart_act_labels[] = $row['act_nombre'];
        $chart_act_data[] = $row['total_horas'];
    }

    // 12. Listado de Reportes Recientes
    $reportes_recientes = [];
    $res_recientes = $conexion->query("
        SELECT r.*, e.nombre as emp_nombre, e.apellido as emp_apellido, a.nombre as act_nombre, p.nombre as pro_nombre 
        FROM reportes_actividades r 
        INNER JOIN empleado e ON r.id_empleado = e.id_empleado 
        INNER JOIN actividades a ON r.id_actividad = a.id_actividad 
        INNER JOIN proyectos p ON r.id_proyecto = p.id_proyecto 
        ORDER BY r.fecha DESC, r.hora_inicio DESC 
        LIMIT 5
    ");
    while ($res_recientes && $row = $res_recientes->fetch_assoc()) {
        $reportes_recientes[] = $row;
    }
} 
// ========================================================
// CONSULTAS PARA ROL EMPLEADO (id_rol = 2)
// ========================================================
else {
    // 1. Sus reportes creados hoy
    $res = $conexion->query("SELECT COUNT(*) FROM reportes_actividades WHERE id_empleado = $id_empleado AND fecha = CURDATE()");
    $reportes_hoy = $res ? $res->fetch_row()[0] : 0;

    // 2. Sus horas hoy
    $res = $conexion->query("SELECT SUM(tiempo_total_minutos) FROM reportes_actividades WHERE id_empleado = $id_empleado AND fecha = CURDATE()");
    $minutos_hoy = $res ? $res->fetch_row()[0] : 0;
    $horas_hoy_texto = minutosAHorasDashboard($minutos_hoy);

    // 3. Sus horas esta semana
    $res = $conexion->query("SELECT SUM(tiempo_total_minutos) FROM reportes_actividades WHERE id_empleado = $id_empleado AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)");
    $minutos_semana = $res ? $res->fetch_row()[0] : 0;
    $horas_semana_texto = minutosAHorasDashboard($minutos_semana);

    // 4. Sus horas este mes
    $res = $conexion->query("SELECT SUM(tiempo_total_minutos) FROM reportes_actividades WHERE id_empleado = $id_empleado AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())");
    $minutos_mes = $res ? $res->fetch_row()[0] : 0;
    $horas_mes_texto = minutosAHorasDashboard($minutos_mes);

    // 5. Su actividad más frecuente
    $res = $conexion->query("
        SELECT a.nombre, COUNT(*) as cnt 
        FROM reportes_actividades r 
        INNER JOIN actividades a ON r.id_actividad = a.id_actividad 
        WHERE r.id_empleado = $id_empleado
        GROUP BY r.id_actividad 
        ORDER BY cnt DESC 
        LIMIT 1
    ");
    $actividad_frecuente = ($res && $row = $res->fetch_assoc()) ? $row['nombre'] : "Ninguna";

    // 6. Su proyecto con más horas
    $res = $conexion->query("
        SELECT p.nombre, SUM(r.tiempo_total_minutos) as total_min 
        FROM reportes_actividades r 
        INNER JOIN proyectos p ON r.id_proyecto = p.id_proyecto 
        WHERE r.id_empleado = $id_empleado
        GROUP BY r.id_proyecto 
        ORDER BY total_min DESC 
        LIMIT 1
    ");
    $proyecto_mas_horas = ($res && $row = $res->fetch_assoc()) ? $row['nombre'] . " (" . minutosAHorasDashboard($row['total_min']) . ")" : "Ninguno";

    // 7. Datos para Gráfico: Horas por Proyecto (Personal)
    $chart_pro_labels = [];
    $chart_pro_data = [];
    $res_chart1 = $conexion->query("
        SELECT p.nombre as pro_nombre, ROUND(SUM(r.tiempo_total_minutos)/60, 1) as total_horas 
        FROM reportes_actividades r 
        INNER JOIN proyectos p ON r.id_proyecto = p.id_proyecto 
        WHERE r.id_empleado = $id_empleado
        GROUP BY r.id_proyecto 
        ORDER BY total_horas DESC
    ");
    while ($res_chart1 && $row = $res_chart1->fetch_assoc()) {
        $chart_pro_labels[] = $row['pro_nombre'];
        $chart_pro_data[] = $row['total_horas'];
    }

    // 8. Datos para Gráfico: Horas por Actividad (Personal)
    $chart_act_labels = [];
    $chart_act_data = [];
    $res_chart2 = $conexion->query("
        SELECT a.nombre as act_nombre, ROUND(SUM(r.tiempo_total_minutos)/60, 1) as total_horas 
        FROM reportes_actividades r 
        INNER JOIN actividades a ON r.id_actividad = a.id_actividad 
        WHERE r.id_empleado = $id_empleado
        GROUP BY r.id_actividad 
        ORDER BY total_horas DESC
    ");
    while ($res_chart2 && $row = $res_chart2->fetch_assoc()) {
        $chart_act_labels[] = $row['act_nombre'];
        $chart_act_data[] = $row['total_horas'];
    }

    // 9. Listado de Sus Reportes Recientes
    $reportes_recientes = [];
    $res_recientes = $conexion->query("
        SELECT r.*, a.nombre as act_nombre, p.nombre as pro_nombre 
        FROM reportes_actividades r 
        INNER JOIN actividades a ON r.id_actividad = a.id_actividad 
        INNER JOIN proyectos p ON r.id_proyecto = p.id_proyecto 
        WHERE r.id_empleado = $id_empleado
        ORDER BY r.fecha DESC, r.hora_inicio DESC 
        LIMIT 5
    ");
    while ($res_recientes && $row = $res_recientes->fetch_assoc()) {
        $reportes_recientes[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConectaWork | Panel de Control</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS del Sistema -->
    <link rel="stylesheet" href="public/css/layout.css">
    <link rel="stylesheet" href="public/css/dashboard.css">
    <link rel="stylesheet" href="public/css/custom.css">

    <!-- Chart.js para Estadísticas Premium -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="layout">
        <!-- Inclusión del Sidebar Lateral -->
        <?php include_once 'includes/sidebar.php'; ?>

        <div class="main">
            <!-- Inclusión de la Cabecera Superior -->
            <?php include_once 'includes/header.php'; ?>

            <!-- Área de Contenido Principal -->
            <main class="content">
                
                <!-- Sección de Bienvenida -->
                <div class="dashboard-welcome">
                    <h2>¡Hola, <?php echo htmlspecialchars($nombre_usuario); ?>! 👋</h2>
                    <p>Aquí tienes un resumen en tiempo real del trabajo y la actividad de ConectaWork.</p>
                </div>

                <!-- Fila de Tarjetas de Métricas (KPI Cards) -->
                <section class="metrics-grid">
                    <?php if ($id_rol == 1): ?>
                        <!-- Admin KPIs -->
                        <div class="metric-card">
                            <div class="metric-info">
                                <span class="metric-label">Colaboradores</span>
                                <span class="metric-value"><?php echo $total_empleados; ?></span>
                                <span class="metric-trend up">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                                    <?php echo $empleados_activos; ?> Activos
                                </span>
                            </div>
                            <div class="metric-icon-box">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Empleado KPIs -->
                        <div class="metric-card">
                            <div class="metric-info">
                                <span class="metric-label">Reportes de Hoy</span>
                                <span class="metric-value"><?php echo $reportes_hoy; ?></span>
                                <span class="metric-trend up">Registros guardados</span>
                            </div>
                            <div class="metric-icon-box">
                                <i class="fa-solid fa-file-invoice" style="font-size:20px; color:var(--primary);"></i>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="metric-card">
                        <div class="metric-info">
                            <span class="metric-label">Horas de Hoy</span>
                            <span class="metric-value"><?php echo $horas_hoy_texto; ?></span>
                            <span class="metric-trend up">
                                <?php echo ($id_rol == 1) ? "$reportes_hoy reportes hoy" : "Hoy"; ?>
                            </span>
                        </div>
                        <div class="metric-icon-box">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-info">
                            <span class="metric-label">Horas esta Semana</span>
                            <span class="metric-value"><?php echo $horas_semana_texto; ?></span>
                            <span class="metric-trend up">Semana actual</span>
                        </div>
                        <div class="metric-icon-box">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-info">
                            <span class="metric-label">Horas este Mes</span>
                            <span class="metric-value"><?php echo $horas_mes_texto; ?></span>
                            <span class="metric-trend up">Mes de <?php echo date('F'); ?></span>
                        </div>
                        <div class="metric-icon-box">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><path d="M12 8v8"></path><path d="M8 12h8"></path></svg>
                        </div>
                    </div>
                </section>

                <!-- Segunda Sección: Actividades y Tareas Destacadas -->
                <div class="content-card" style="padding: 20px;">
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: space-between;">
                        <div style="flex: 1; min-width: 250px;">
                            <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Actividad más frecuente</span>
                            <h4 style="font-size:1.1rem; font-weight:700; color:var(--accent); margin-top:4px;"><?php echo htmlspecialchars($actividad_frecuente); ?></h4>
                        </div>
                        <div style="flex: 1; min-width: 250px;">
                            <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Proyecto con más horas</span>
                            <h4 style="font-size:1.1rem; font-weight:700; color:var(--primary); margin-top:4px;"><?php echo htmlspecialchars($proyecto_mas_horas); ?></h4>
                        </div>
                        <?php if ($id_rol == 1): ?>
                            <div style="flex: 1; min-width: 250px;">
                                <span style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Empleado con más horas</span>
                                <h4 style="font-size:1.1rem; font-weight:700; color:var(--success); margin-top:4px;"><?php echo htmlspecialchars($empleado_mas_horas); ?></h4>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ========================================================
                   SECCIÓN DE GRÁFICOS INTERACTIVOS (CHART.JS)
                   ======================================================== -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px;">
                    <!-- Gráfico 1: Horas por empleado / Proyecto -->
                    <div class="content-card">
                        <h3 class="content-card-title">
                            <i class="fa-solid fa-chart-column" style="color: var(--primary);"></i>
                            <?php echo ($id_rol == 1) ? 'Distribución de Horas por Colaborador' : 'Horas Registradas por Proyecto'; ?>
                        </h3>
                        <div style="position: relative; height: 260px; width: 100%;">
                            <canvas id="chartHorasEmpProj"></canvas>
                        </div>
                    </div>

                    <!-- Gráfico 2: Horas por Actividad -->
                    <div class="content-card">
                        <h3 class="content-card-title">
                            <i class="fa-solid fa-chart-pie" style="color: var(--accent);"></i>
                            Distribución de Horas por Actividad
                        </h3>
                        <div style="position: relative; height: 260px; width: 100%;">
                            <canvas id="chartHorasActividad"></canvas>
                        </div>
                    </div>
                </div>

                <!-- ========================================================
                   LISTADO DE REPORTES RECIENTES
                   ======================================================== -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="content-card-title">
                            <i class="fa-regular fa-calendar-check" style="color: var(--accent);"></i>
                            <?php echo ($id_rol == 1) ? 'Últimos Reportes Registrados' : 'Mis Reportes Recientes'; ?>
                        </h3>
                        <a href="reportes.php" class="btn-custom btn-custom-secondary btn-custom-sm">Ver Todos</a>
                    </div>
                    
                    <div class="table-responsive-custom">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <?php if ($id_rol == 1): ?>
                                        <th>Colaborador</th>
                                    <?php endif; ?>
                                    <th>Fecha</th>
                                    <th>Proyecto</th>
                                    <th>Actividad</th>
                                    <th>Horario</th>
                                    <th>Duración</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($reportes_recientes) > 0): ?>
                                    <?php foreach ($reportes_recientes as $rep): ?>
                                        <tr>
                                            <?php if ($id_rol == 1): ?>
                                                <td>
                                                    <span style="font-weight:600;"><?php echo htmlspecialchars($rep['emp_nombre'] . ' ' . $rep['emp_apellido']); ?></span>
                                                </td>
                                            <?php endif; ?>
                                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($rep['fecha']))); ?></td>
                                            <td><span style="font-weight:500;"><?php echo htmlspecialchars($rep['pro_nombre']); ?></span></td>
                                            <td>
                                                <span class="badge-custom badge-custom-primary"><?php echo htmlspecialchars($rep['act_nombre']); ?></span>
                                            </td>
                                            <td style="font-family: monospace; font-size:0.75rem;">
                                                <?php echo htmlspecialchars(date('g:i A', strtotime($rep['hora_inicio']))) . ' - ' . htmlspecialchars(date('g:i A', strtotime($rep['hora_fin']))); ?>
                                            </td>
                                            <td>
                                                <span style="font-weight:600; color:var(--accent);">
                                                    <?php echo minutosAHorasDashboard($rep['tiempo_total_minutos']); ?>
                                                </span>
                                            </td>
                                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.78rem; color:var(--text-muted);">
                                                <?php echo htmlspecialchars($rep['descripcion']); ?>
                                            </td>
                                            <td>
                                                <span class="badge-custom <?php echo ($rep['estado'] === 'Finalizado') ? 'badge-custom-success' : 'badge-custom-warning'; ?>">
                                                    <?php echo htmlspecialchars($rep['estado']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo ($id_rol == 1) ? '8' : '7'; ?>" style="text-align: center; color: var(--text-muted); padding: 20px;">
                                            No hay registros de actividades recientes.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Encabezado de la Sección de Noticias (Mockup Visor Derecho) -->
                <div class="news-section-header">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        Novedades y Anuncios Internos
                    </h3>
                </div>

                <!-- Grilla de Tarjetas Interactivas de Novedades (4 Tarjetas del Mockup) -->
                <section class="news-grid">
                    
                    <!-- Tarjeta 1: Reunión Presencial de Equipo -->
                    <article class="news-card">
                        <div class="news-card-image">
                            <div class="news-card-pattern"></div>
                            <span class="news-card-badge">Importante</span>
                            <div class="news-card-header-content">
                                <div class="news-card-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </div>
                                <div class="news-card-meta">
                                    <span class="news-card-tag">Operaciones</span>
                                    <h4 class="news-card-title">Reunión Presencial de Equipo</h4>
                                </div>
                            </div>
                        </div>
                        <div class="news-card-body">
                            <p class="news-card-desc">
                                ¡Nos vemos pronto! Próxima reunión de alineación presencial para coordinar los objetivos del segundo trimestre y la integración de nuevos colaboradores.
                            </p>
                            <div class="news-card-extra">
                                <div class="meeting-details">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <span>Jueves, 09:30 AM · Auditorio</span>
                                </div>
                                <button class="btn-confirm" id="btnConfirmAttendance">Confirmar Asistencia</button>
                            </div>
                        </div>
                    </article>

                    <!-- Tarjeta 2: Nuestras Redes Sociales -->
                    <article class="news-card">
                        <div class="news-card-image">
                            <div class="news-card-pattern"></div>
                            <span class="news-card-badge">Comunidad</span>
                            <div class="news-card-header-content">
                                <div class="news-card-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                                </div>
                                <div class="news-card-meta">
                                    <span class="news-card-tag">Social</span>
                                    <h4 class="news-card-title">Nuestras Redes Sociales</h4>
                                </div>
                            </div>
                        </div>
                        <div class="news-card-body">
                            <p class="news-card-desc">
                                ¡Ya somos más en la comunidad digital! Sigue nuestra cuenta de LinkedIn para enterarte de los logros de la compañía y compartir nuestras ofertas de empleo con tus conocidos.
                            </p>
                            <div class="news-card-extra">
                                <div class="social-stats">
                                    <div class="social-stat-item">
                                        <span class="social-stat-val">+1,200</span>
                                        <span class="social-stat-lbl">Seguidores</span>
                                    </div>
                                    <div class="social-stat-item">
                                        <span class="social-stat-val">LinkedIn</span>
                                        <span class="social-stat-lbl">Canal Principal</span>
                                    </div>
                                </div>
                                <a href="https://linkedin.com" target="_blank" class="link-social">
                                    Ver Comunidad
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                </a>
                            </div>
                        </div>
                    </article>

                    <!-- Tarjeta 3: Capacitación y Aprendizaje -->
                    <article class="news-card">
                        <div class="news-card-image">
                            <div class="news-card-pattern"></div>
                            <span class="news-card-badge">Formación</span>
                            <div class="news-card-header-content">
                                <div class="news-card-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                </div>
                                <div class="news-card-meta">
                                    <span class="news-card-tag">Desarrollo</span>
                                    <h4 class="news-card-title">Capacitación y Aprendizaje</h4>
                                </div>
                            </div>
                        </div>
                        <div class="news-card-body">
                            <p class="news-card-desc">
                                Resumen: Jornada de Aprendizaje en Liderazgo. Recuerda completar las lecturas del módulo 2 y el cuestionario práctico antes de este viernes a medianoche.
                            </p>
                            <div class="progress-container">
                                <div class="progress-info">
                                    <span>Progreso del Curso</span>
                                    <span>65%</span>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill"></div>
                                </div>
                            </div>
                            <div class="news-card-extra">
                                <span>Asignado por: Gestión Humana</span>
                                <a href="#" class="link-learn">
                                    Ver Curso
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                </a>
                            </div>
                        </div>
                    </article>

                    <!-- Tarjeta 4: Bienestar o Recordatorio General -->
                    <article class="news-card">
                        <div class="news-card-image">
                            <div class="news-card-pattern"></div>
                            <span class="news-card-badge">Salud</span>
                            <div class="news-card-header-content">
                                <div class="news-card-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path></svg>
                                </div>
                                <div class="news-card-meta">
                                    <span class="news-card-tag">Cuidado</span>
                                    <h4 class="news-card-title">Bienestar y Recordatorio</h4>
                                </div>
                            </div>
                        </div>
                        <div class="news-card-body">
                            <p class="news-card-desc">
                                Tu bienestar es nuestra prioridad. Realiza pausas activas cada 2 horas para estirar el cuerpo y descansar la vista de las pantallas. Cuidemos nuestra ergonomía diaria.
                            </p>
                            <div class="wellbeing-tip">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                <span>Tip: Ajusta el brillo del monitor y mantén los hombros relajados al teclear.</span>
                            </div>
                            <div class="news-card-extra">
                                <span>Pausas Activas</span>
                                <a href="#" class="link-download">
                                    Descargar Guía
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                </a>
                            </div>
                        </div>
                    </article>

                </section>
            </main>

            <!-- Script dinámico para confirmar asistencia en Tarjeta 1 (Reunión) -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const btnConfirm = document.getElementById('btnConfirmAttendance');
                if (btnConfirm) {
                    const isConfirmed = sessionStorage.getItem('attendanceConfirmed') === 'true';
                    if (isConfirmed) {
                        setConfirmedUI();
                    }

                    btnConfirm.addEventListener('click', function() {
                        if (!sessionStorage.getItem('attendanceConfirmed')) {
                            sessionStorage.setItem('attendanceConfirmed', 'true');
                            setConfirmedUI();
                            alert('¡Asistencia confirmada con éxito! Te hemos reservado un espacio.');
                        }
                    });
                }

                function setConfirmedUI() {
                    btnConfirm.textContent = '✓ Asistencia Confirmada';
                    btnConfirm.classList.add('confirmed');
                    btnConfirm.disabled = true;
                }

                // -------------------------------------------------------------
                // CONFIGURACIÓN DE LOS GRÁFICOS PREMIUM CON CHART.JS
                // -------------------------------------------------------------
                
                // Colores consistentes con variables.css
                const primaryColor = '#8b5cf6'; // Morado
                const accentColor = '#06b6d4';  // Turquesa
                const successColor = '#10b981'; // Verde
                const warningColor = '#f59e0b'; // Ámbar
                const dangerColor = '#ef4444';  // Rojo
                const textMutedColor = '#94a3b8';
                const borderColor = 'rgba(255, 255, 255, 0.08)';

                // Configuración común
                const commonOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: textMutedColor,
                                font: { family: 'Poppins', size: 11 }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: borderColor },
                            ticks: { color: textMutedColor, font: { family: 'Poppins', size: 10 } }
                        },
                        y: {
                            grid: { color: borderColor },
                            ticks: { color: textMutedColor, font: { family: 'Poppins', size: 10 } }
                        }
                    }
                };

                // GRÁFICO 1: Horas por Colaborador (Admin) o Proyecto (Empleado)
                const ctx1 = document.getElementById('chartHorasEmpProj').getContext('2d');
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($id_rol == 1 ? $chart_emp_labels : $chart_pro_labels); ?>,
                        datasets: [{
                            label: 'Horas Totales',
                            data: <?php echo json_encode($id_rol == 1 ? $chart_emp_data : $chart_pro_data); ?>,
                            backgroundColor: 'rgba(139, 92, 246, 0.55)',
                            borderColor: primaryColor,
                            borderWidth: 1.5,
                            borderRadius: 6
                        }]
                    },
                    options: commonOptions
                });

                // GRÁFICO 2: Horas por Actividad
                const ctx2 = document.getElementById('chartHorasActividad').getContext('2d');
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($chart_act_labels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($chart_act_data); ?>,
                            backgroundColor: [
                                'rgba(6, 182, 212, 0.65)',   // Turquesa
                                'rgba(139, 92, 246, 0.65)',  // Morado
                                'rgba(16, 185, 129, 0.65)',  // Verde
                                'rgba(245, 158, 11, 0.65)',  // Ámbar
                                'rgba(239, 68, 68, 0.65)',   // Rojo
                                'rgba(148, 163, 184, 0.65)'  // Gris
                            ],
                            borderColor: '#151d30',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: textMutedColor,
                                    font: { family: 'Poppins', size: 10 }
                                }
                            }
                        }
                    }
                });

            });
            </script>

            <!-- Inclusión del Footer y cierre de etiquetas -->
            <?php include_once 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>