<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_rol = $_SESSION['id_rol'] ?? 2;
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';
$apellido_usuario = $_SESSION['apellido'] ?? '';
$nombre_completo = trim($nombre_usuario . ' ' . $apellido_usuario);

// Calcular iniciales del perfil para el sidebar
$iniciales_sidebar = '';
if (!empty($nombre_usuario)) {
    $iniciales_sidebar .= mb_substr($nombre_usuario, 0, 1);
}
if (!empty($apellido_usuario)) {
    $iniciales_sidebar .= mb_substr($apellido_usuario, 0, 1);
}
$iniciales_sidebar = strtoupper($iniciales_sidebar);
if (empty($iniciales_sidebar)) {
    $iniciales_sidebar = "CW";
}

// Obtener página actual para resaltar en menú
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <!-- Logotipo -->
    <div class="logo">
        <span class="logo-dot">●</span>
        <span class="logo-text">ConectaWork</span>
    </div>

    <!-- Perfil del Colaborador -->
    <div class="sidebar-profile">
        <div class="profile-avatar-container">
            <div class="avatar-lg"><?php echo htmlspecialchars($iniciales_sidebar); ?></div>
            <span class="status-indicator"></span>
        </div>
        <div class="user-info-text">
            <span class="user-greeting">¡Te damos la bienvenida!</span>
            <span class="user-name"><?php echo htmlspecialchars($nombre_completo); ?></span>
        </div>
    </div>

    <!-- Navegación de Módulos (Filtrado por Rol) -->
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-section">Menú Principal</li>
            
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- OPCIONES EXCLUSIVAS DE ADMINISTRADOR -->
            <?php if ($id_rol == 1): ?>
                <li class="<?php echo ($current_page == 'empleados.php') ? 'active' : ''; ?>">
                    <a href="empleados.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span>Empleados</span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                        <span>Áreas</span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                        <span>Cargos</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-section">Gestión Personal</li>

            <!-- Reporte de Actividades (Accesible para ambos, pero muestra contenido diferente) -->
            <li class="<?php echo ($current_page == 'reportes.php') ? 'active' : ''; ?>">
                <a href="reportes.php">
                    <!-- Icono de clipboard/actividades -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Reporte Actividades</span>
                </a>
            </li>

            <?php if ($id_rol == 1): ?>
                <!-- Estas opciones de prototipo sólo se muestran al admin por integridad del mockup anterior -->
                <li>
                    <a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span>Permisos</span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><path d="M12 8v8"></path><path d="M8 12h8"></path></svg>
                        <span>Incapacidades</span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        <span>Vacaciones</span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        <span>Capacitaciones</span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>Novedades</span>
                    </a>
                </li>

                <li class="nav-section">Operaciones</li>

                <li>
                    <a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        <span>Nómina</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="<?php echo ($current_page == 'configuracion.php') ? 'active' : ''; ?>">
                <a href="configuracion.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>Configuración</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer-menu">
            <a href="logout.php" class="btn-logout">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </nav>
</aside>