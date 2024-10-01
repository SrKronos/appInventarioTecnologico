<?php
session_start();
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['nombre']) || empty($_SESSION['usuario']['fechahora'])) {
    echo "rebotado";
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/sweetalert2@11.js"></script>
    <link rel="stylesheet" href="../css/estilo_administrar.css">
    <link rel="stylesheet" href="../css/estilo_principal.css">

</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-dark text-white">
            <div class="sidebar-header p-3">
                <h3 class="mb-0">Bienvenido <?php echo $_SESSION['usuario']['nombre']; ?></h3>
            </div>
            <ul class="list-unstyled components p-3">
                <li>
                    <a href="#" class="nav-link text-white" data-page="administrar_productos.php">Administrar Productos</a>
                </li>   
                <li>
                    <a href="#" class="nav-link text-white" data-page="administrar_movimientos.php">Administrar Movimientos</a>
                </li>               
                <li>
                    <a href="" id="btnCerrarSesion" class="nav-link text-white" data-page="cerrar_sesion.php">Cerrar Sesión</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <h1 class="navbar-brand mb-0">Panel de Administración</h1>
                </div>
            </nav>

            <div id="main-content" class="container mt-4">
                <!-- El contenido dinámico se cargará aquí -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const content = document.getElementById('main-content');
            const menuItems = document.querySelectorAll('a[data-page]');

            sidebarCollapse.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });

            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    loadContent(page);
                    if (window.innerWidth <= 768) {
                        sidebar.classList.toggle('active');
                    }
                });
            });

            function loadContent(page) {
                fetch(page)
                    .then(response => response.text())
                    .then(html => {
                        content.innerHTML = html;
                        // Buscar e inyectar los scripts manualmente
                        const scripts = content.querySelectorAll('script');
                        scripts.forEach(script => {
                            const newScript = document.createElement('script');
                            newScript.src = script.src || null;
                            newScript.innerHTML = script.innerHTML;
                            document.body.appendChild(newScript);
                        });

                    })
                    .catch(error => {
                        console.error('Error al cargar el contenido:', error);
                        content.innerHTML = '<p class="alert alert-danger">Error al cargar el contenido.</p>';
                    });
            }



            //Cerrar Sesion
            $("#btnCerrarSesion").click(function() {
                $.ajax({
                    url: "../controller/inicio.php",
                    type: "POST",
                    datatype: "JSON",
                    data: {
                        opt: "cerrarsesion",
                    },
                    success: function(response) {
                        // Asegúrate de que la respuesta está en formato JSON
                        try {
                            const jsonResponse = JSON.parse(response);
                            if (jsonResponse.response === 'ok') {
                                // Redirige al login tras cerrar sesión
                                window.location.href = "../view/index.php";
                            } else {
                                console.log("Error al cerrar sesión: ", jsonResponse);
                            }
                        } catch (e) {
                            console.error("Error al procesar la respuesta de cierre de sesión:", e);
                        }
                    },
                    error: function() {
                        console.log("Error al Cerrar Sesión");
                    },
                });
            });


            // Cargar contenido inicial
            loadContent('administrar_movimientos.php');
        });
    </script>
</body>

</html>