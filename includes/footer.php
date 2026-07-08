    <footer class="footer">
        <div class="footer-brand">© 2026 ConectaWork</div>
        <div class="footer-version">Versión 1.0.0 (Prototipo ADSO)</div>
    </footer>
</div> <!-- Fin de .main -->
</div> <!-- Fin de .layout -->

<!-- JavaScript para interactividad del Dashboard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // -------------------------------------------------------------
    // 1. CONTROL DE SIDEBAR RESPONSIVO
    // -------------------------------------------------------------
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');

    if (toggleSidebar && sidebar) {
        toggleSidebar.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });

        // Cerrar sidebar al hacer clic fuera en móviles
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                if (!sidebar.contains(e.target) && e.target !== toggleSidebar) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }
});
</script>
</body>
</html>
