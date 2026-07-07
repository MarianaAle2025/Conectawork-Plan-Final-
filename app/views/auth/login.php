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

                <form action="#" method="POST">

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