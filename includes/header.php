<?php
// Evitamos depender del localismo del servidor de XAMPP (que suele fallar en Windows)
$dias_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

$dia_nombre = $dias_semana[date('w')];
$dia_numero = date('d');
$mes_nombre = $meses[date('n') - 1];
$anio = date('Y');

$fecha_formateada = "$dia_nombre, $dia_numero de $mes_nombre de $anio";

// Calcular iniciales del perfil
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$iniciales = '';
if (!empty($_SESSION['nombre'])) {
    $iniciales .= mb_substr($_SESSION['nombre'], 0, 1);
}
if (!empty($_SESSION['apellido'])) {
    $iniciales .= mb_substr($_SESSION['apellido'], 0, 1);
}
$iniciales = strtoupper($iniciales);
if (empty($iniciales)) {
    $iniciales = "CW";
}
?>
<header class="header">
    <!-- Botón Toggle de Sidebar para móviles -->
    <button class="btn-toggle-sidebar" id="toggleSidebar" aria-label="Abrir Menú">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <!-- Buscador global -->
    <div class="header-search">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="search-icon">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" placeholder="Buscar empleados, reportes, actividades...">
    </div>

    <!-- Panel de control de usuario -->
    <div class="header-actions">
        <!-- Fecha dinámica -->
        <span class="header-date"><?php echo $fecha_formateada; ?></span>

        <!-- Notificaciones -->
        <div class="notifications-container">
            <button class="btn-icon" aria-label="Notificaciones" id="notificationsBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="badge-pulse"></span>
            </button>
        </div>

        <!-- Separador visual -->
        <div class="header-divider"></div>

        <!-- Perfil rápido -->
        <div class="header-profile">
            <div class="avatar">
                <span><?php echo htmlspecialchars($iniciales); ?></span>
            </div>
            <div class="profile-info">
                <span class="profile-name"><?php echo htmlspecialchars(($_SESSION['nombre'] ?? 'Usuario') . ' ' . ($_SESSION['apellido'] ?? '')); ?></span>
                <span class="profile-role"><?php echo htmlspecialchars($_SESSION['cargo'] ?? ($_SESSION['rol_nombre'] ?? 'Colaborador')); ?></span>
            </div>
        </div>
    </div>
</header>