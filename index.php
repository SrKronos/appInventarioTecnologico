<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/inicio.css">
    <script src="js/jquery-3.7.1.min.js"></script>
    <script src="js/sweetalert2@11.js"></script>
    <script src="js/jspdf.umd.min.js"></script>
    <script src="https://unpkg.com/react@17/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/matter-js/0.17.1/matter.min.js"></script>
    
</head>

<body>
    <div id="emoji-container"></div>
    
    <div class="page-container">
        <div class="container">
            <h3>TIENDA TECNOLOGICA</h3>
            <form>
                <label for="usuario">Correo:</label>
                <input type="email" id="usuario" name="usuario" placeholder="su_email@correo.com" value="leo@mail.com">
                <label for="clave">Clave:</label>
                <input type="password" id="clave" name="clave" placeholder="******" value="123">
                <button type="button" id="btnLogin" name="btnLogin">Login</button>
            </form>
        </div>
    </div>
    
    <div class="watermark">Derechos Reservados Leonardo Loor - 2024 v1.0.1 &reg;</div>   
    <script src="js/index.js"></script>
</body>

</html>