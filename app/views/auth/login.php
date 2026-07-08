<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ConectaWork | Login</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="public/css/login.css">

</head>

<body>

    <main class="contenedor">

        <!-- Panel izquierdo -->
        <section class="izquierda">

            <div class="contenido">

                <span class="logo">● ConectaWork</span>

                <h1>
                    Gestiona tu equipo de trabajo desde un solo lugar.
                </h1>

                <p>

                    Administra empleados, áreas, permisos, incapacidades,
                    tareas y capacitaciones de forma sencilla,
                    moderna y segura.

                </p>

                <ul>

                    <li>✔ Gestión de empleados</li>
                    <li>✔ Control de permisos</li>
                    <li>✔ Incapacidades</li>
                    <li>✔ Capacitaciones</li>

                </ul>

            </div>

        </section>

        <!-- Panel derecho -->

        <section class="derecha">

            <div class="card-login">

                <h2>Bienvenido 👋</h2>

                <p class="subtitulo">

                    Inicia sesión para continuar

                </p>

                <?php if (!empty($error)): ?>
                    <div class="alerta-error" style="background-color: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; line-height: 1.4; display: flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST">

                    <div class="grupo">

                        <label>Correo electrónico</label>

                        <input
                            type="email"
                            name="correo"
                            placeholder="admin@conectawork.com">

                    </div>

                    <div class="grupo">

                        <label>Contraseña</label>

                        <input
                            type="password"
                            name="password"
                            placeholder="********">

                    </div>

                    <button type="submit">

                        Ingresar

                    </button>

                </form>

            </div>

        </section>

    </main>

</body>

</html>