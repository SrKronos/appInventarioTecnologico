<?php
require_once __DIR__ . '/../db/ConexionPostgresql.php';


$opcion = $_POST["opt"];
switch ($opcion) {
    case "login":
        Login();
        break;
    case "cerrarsesion":
        CerrarSession();
        break;
    default:
        echo json_encode(array('error' => 'Opción no válida.'));
        break;
}


function Login()
{
   
    $respuesta = array();
    
    // Verificar si los datos de usuario y clave han sido enviados
    if (isset($_POST['usuario']) && isset($_POST['clave'])) {

 
        $email = trim($_POST['usuario']);
        $clave = $_POST['clave'];
        
        // Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $respuesta['response'] = 'error';
            $respuesta['mensaje'] = 'Formato de email inválido';
            echo json_encode($respuesta);
            return;
        }
 
        // Iniciar conexión con PostgreSQL
        try {

            $con = new ConexionPostgresql();

            $pdo = $con->obtenerConexion();

        } catch (PDOException $e) {
            $respuesta['response'] = 'error';
            $respuesta['mensaje'] = 'Error en la conexión con la base de datos: ' . $e->getMessage();
            echo json_encode($respuesta);
            return;
        }
        
        // Preparar la consulta para obtener la clave encriptada
        $query = "SELECT username as email,username as nombres,password as clave FROM public.usuarios WHERE username = :email";
        
        try {
            $smt = $pdo->prepare($query);
            $smt->bindParam(':email', $email);
            $smt->execute();
            // Verificar si se encontró el usuario
            if ($smt->rowCount() > 0) {
                $usuario = $smt->fetch(PDO::FETCH_ASSOC);
                // Verificar la contraseña usando password_verify
                if ($usuario['clave']===$clave) {
                    $respuesta['response'] = 'ok';
                    // Eliminar la clave antes de retornar los datos del usuario
                    unset($usuario['clave']);
                    $respuesta['data'] = $usuario;
                    $respuesta['mensaje'] = '¡Login Exitoso!';
                    
                    // Iniciar la sesión y establecer variables de sesión
                    session_start();
                    $_SESSION['usuario'] = array(
                        'nombre' => $usuario['nombres'],
                        'fechahora' => date('Y-m-d H:i:s')
                    );
                } else {
                    $respuesta['response'] = 'advertencia';
                    $respuesta['mensaje'] = 'Clave incorrecta';
                }
            } else {
                $respuesta['response'] = 'advertencia';
                $respuesta['mensaje'] = 'Usuario no existe!';
            }
        } catch (PDOException $e) {
            $respuesta['response'] = 'error';
            $respuesta['mensaje'] = 'Error en la consulta: ' . $e->getMessage();
        }

        // Cerrar la conexión
        $con->cerrarConexion();
    } else {
        $respuesta['response'] = 'error';
        $respuesta['mensaje'] = 'Ingrese un usuario y una contraseña';
    }

    // Retornar la respuesta en formato JSON
    echo json_encode($respuesta);
}



function CerrarSession()
{
    session_start(); // Inicia la sesión si no está iniciada
    session_unset(); // Elimina todas las variables de sesión
    session_destroy(); // Destruye la sesión actual

    // Opcionalmente, puedes borrar las cookies de sesión si se están utilizando
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    $respuesta['response'] = 'ok';    
    echo json_encode($respuesta);
}

