<?php
require_once __DIR__ .  "/../db/ConexionPostgresql.php";

$opcion = $_POST["opt"];
switch ($opcion) {
    case "obtenerProductos":
        obtenerProductos();
        break;
    case "guardarProducto":
        guardarProductoNew();
        break;
    case "obtenerMovimientos":
        obtenerMovimientos();
        break;
    case "registrarMovimiento":
        guardarMovimiento();
        break;
    case "eliminarMovimiento":
        eliminarMovimiento();
        break;
    case "buscarProducto":
        obtenerProductos();
        break;
    default:
        echo json_encode(array('error' => 'Opción no válida.'));
        break;
}
function eliminarMovimiento() {
    // Verificar si se ha recibido la variable id por POST
    if (!isset($_POST['producto']['id'])) {
        return json_encode(array('success' => false, 'message' => 'ID del movimiento no proporcionado.'));
    }

    // Obtener el ID del movimiento a eliminar
    $idHistorial = (int)$_POST['producto']['id']; 
    $conexion = new ConexionPostgresql(); // Asumiendo que tienes una clase para manejar la conexión
    $pdo = $conexion->obtenerConexion();
    
    // Inicializar la respuesta
    $respuesta = array('success' => false, 'message' => '');

    try {
        // Iniciar la transacción
        $pdo->beginTransaction();

        // Buscar el movimiento en el historial
        $stmtHistorial = $pdo->prepare("SELECT fkproducto, cantidad, tipo FROM public.historial WHERE id = :id");
        $stmtHistorial->bindParam(':id', $idHistorial, PDO::PARAM_INT);
        $stmtHistorial->execute();

        if ($stmtHistorial->rowCount() > 0) {
           
            $movimiento = $stmtHistorial->fetch(PDO::FETCH_ASSOC);
            $fkproducto = $movimiento['fkproducto'];
            $cantidad = $movimiento['cantidad'];
            $tipo = $movimiento['tipo'];
           
            // Obtener el stock actual del producto
            $stmtProducto = $pdo->prepare("SELECT stock FROM public.producto WHERE id = :id");
            $stmtProducto->bindParam(':id', $fkproducto, PDO::PARAM_INT);
            $stmtProducto->execute();
           
            if ($stmtProducto->rowCount() > 0) {
                $productoData = $stmtProducto->fetch(PDO::FETCH_ASSOC);
                $stockActual = $productoData['stock'];
                $nuevoStock = 0;

                // Actualizar el stock según el tipo de movimiento
                switch ($tipo) {
                    case 'I':
                        $nuevoStock = $stockActual - $cantidad;
                        if ($nuevoStock < 0) {
                            $respuesta['success'] = false;
                            $respuesta['message'] = "No se puede eliminar el ingreso. El stock resultante sería negativo.";
                            return json_encode($respuesta); // Devolver respuesta en formato JSON
                        }
                        break;
                    case 'E':
                        $nuevoStock = $stockActual + $cantidad;
                        break;
                    default:
                        $respuesta['success'] = false;
                        $respuesta['message'] = "Tipo de movimiento no válido.";
                        return json_encode($respuesta); // Devolver respuesta en formato JSON
                }
                
                // Actualizar el stock en la tabla producto
                $stmtActualizarStock = $pdo->prepare("UPDATE public.producto SET stock = :nuevoStock WHERE id = :id");
                $stmtActualizarStock->bindParam(':nuevoStock', $nuevoStock, PDO::PARAM_INT);
                $stmtActualizarStock->bindParam(':id', $fkproducto, PDO::PARAM_INT);
                if (!$stmtActualizarStock->execute()) {
                    // Si la actualización falla, revertir la transacción
                    $pdo->rollBack();
                    $respuesta['success'] = false;
                    $respuesta['message'] = "Error al actualizar el stock del producto.";
                    return json_encode($respuesta); // Devolver respuesta en formato JSON
                }
                
                // Eliminar el registro del historial
                $stmtEliminarHistorial = $pdo->prepare("DELETE FROM public.historial WHERE id = :id");
                $stmtEliminarHistorial->bindParam(':id', $idHistorial, PDO::PARAM_INT);
                if (!$stmtEliminarHistorial->execute()) {
                    // Si la eliminación falla, revertir la transacción
                    $pdo->rollBack();
                    $respuesta['success'] = false;
                    $respuesta['message'] = "Error al eliminar el movimiento del historial.";
                    return json_encode($respuesta); // Devolver respuesta en formato JSON
                }
                
                // Si todo salió bien, confirmar la transacción
                $pdo->commit();
                $respuesta['success'] = true;
                $respuesta['message'] = "Movimiento eliminado exitosamente.";
            } else {
                
                $respuesta['success'] = false;
                $respuesta['message'] = "No se encontró el producto asociado al movimiento.";
            }
        } else {
            $respuesta['success'] = false;
            $respuesta['message'] = "No se encontró el movimiento especificado.";
        }
    } catch (PDOException $e) {
        // Si hay un error, revertir la transacción
        $pdo->rollBack();
        $respuesta['success'] = false;
        $respuesta['message'] = "Error en sentencia SQL: " . $e->getMessage();
    }

    echo json_encode($respuesta); // Devolver respuesta en formato JSON
}

function guardarMovimiento()
{
    // Verificar si se recibió correctamente la solicitud POST
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Obtener los datos del movimiento desde AJAX
        $producto = $_POST['producto']; // Convertir JSON a array asociativo 

        // Validar que los campos requeridos no estén vacíos
        $camposObligatorios = ['id', 'cantidad', 'tipo'];
        foreach ($camposObligatorios as $campo) {
            if (empty($producto[$campo]) && $producto[$campo] !== '0') {
                // Devolver la respuesta con error si algún campo está vacío
                $response = array('success' => false, 'message' => "El campo '$campo' no puede estar vacío.");
                header('Content-Type: application/json');
                echo json_encode($response);
                exit; // Detener la ejecución si hay un campo vacío
            }
        }

        // Crear instancia de conexión
        $conexion = new ConexionPostgresql();
        $pdo = $conexion->obtenerConexion();

        try {
            $pdo->beginTransaction();

            // Obtener el stock actual del producto
            $stmtStock = $pdo->prepare("SELECT stock FROM public.producto WHERE id = :id");
            $stmtStock->bindParam(':id', $producto['id'], PDO::PARAM_INT);
            $stmtStock->execute();

            if ($stmtStock->rowCount() > 0) {
                $productoData = $stmtStock->fetch(PDO::FETCH_ASSOC);
                $stockActual = $productoData['stock'];
                $nuevoStock = $stockActual;

                // Calcular el nuevo stock según el tipo de movimiento
                if ($producto['tipo'] === 'I') {
                    // Ingreso: sumar la cantidad al stock
                    $nuevoStock += (int)$producto['cantidad'];
                } elseif ($producto['tipo'] === 'E') {
                    // Egreso: restar la cantidad del stock
                    if ($stockActual < (int)$producto['cantidad']) {
                        // Validar que no se quede en negativo
                        $response = array('success' => false, 'message' => "No se puede realizar el egreso. El stock resultante sería negativo.");
                        header('Content-Type: application/json');
                        echo json_encode($response);
                        exit; // Detener la ejecución si el stock sería negativo
                    }
                    $nuevoStock -= (int)$producto['cantidad'];
                } else {
                    $response = array('success' => false, 'message' => "Tipo de movimiento no válido.");
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit; // Detener la ejecución si el tipo no es válido
                }

                // Insertar el nuevo movimiento en la tabla historial
                $sql = "INSERT INTO historial (fkproducto, cantidad, tipo, fecha) 
                        VALUES (:fkproducto, :cantidad, :tipo, now())";

                // Preparar la consulta
                $stmt = $pdo->prepare($sql);

                // Bind de parámetros
                $stmt->bindValue(':fkproducto', $producto['id'], PDO::PARAM_INT);
                $stmt->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
                $stmt->bindValue(':tipo', $producto['tipo'], PDO::PARAM_STR); // 'I' para Ingreso, 'E' para Egreso

                // Ejecutar la consulta para guardar el movimiento
                $stmt->execute();

                // Actualizar el stock en la tabla producto
                $stmtActualizarStock = $pdo->prepare("UPDATE public.producto SET stock = :nuevoStock WHERE id = :id");
                $stmtActualizarStock->bindParam(':nuevoStock', $nuevoStock, PDO::PARAM_INT);
                $stmtActualizarStock->bindParam(':id', $producto['id'], PDO::PARAM_INT);
                $stmtActualizarStock->execute();

                // Confirmar transacción
                $pdo->commit();

                // Preparar la respuesta JSON
                $response = array('success' => true, 'message' => 'Movimiento registrado exitosamente');
            } else {
                $response = array('success' => false, 'message' => "No se encontró el producto con ID: " . $producto['id']);
            }
        } catch (PDOException $e) {
            // Rollback en caso de error
            $pdo->rollBack();

            // Preparar la respuesta JSON de error
            $response = array('success' => false, 'message' => 'Error: ' . $e->getMessage());
        }

        // Devolver la respuesta como JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Si no es una solicitud POST válida
        header("HTTP/1.1 405 Method Not Allowed");
        exit;
    }
}


function guardarMovimiento_old()
{
    // Verificar si se recibió correctamente la solicitud POST
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Obtener los datos del movimiento desde AJAX
        $producto = $_POST['producto']; // Convertir JSON a array asociativo 

        // Validar que los campos requeridos no estén vacíos
        $camposObligatorios = ['id', 'cantidad', 'tipo'];
        foreach ($camposObligatorios as $campo) {
            if (empty($producto[$campo]) && $producto[$campo] !== '0') {
                // Devolver la respuesta con error si algún campo está vacío
                $response = array('success' => false, 'message' => "El campo '$campo' no puede estar vacío.");
                header('Content-Type: application/json');
                echo json_encode($response);
                exit; // Detener la ejecución si hay un campo vacío
            }
        }

        // Crear instancia de conexión
        $conexion = new ConexionPostgresql();
        $pdo = $conexion->obtenerConexion();

        try {
            $pdo->beginTransaction();

            // Insertar el nuevo movimiento en la tabla historial
            $sql = "INSERT INTO historial (fkproducto, cantidad, tipo, fecha) 
                    VALUES (:fkproducto, :cantidad, :tipo, now())";

            // Preparar la consulta
            $stmt = $pdo->prepare($sql);

            // Bind de parámetros
            $stmt->bindValue(':fkproducto', $producto['id'], PDO::PARAM_INT);
            $stmt->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $producto['tipo'], PDO::PARAM_STR); // 'I' para Ingreso, 'E' para Egreso

            // Ejecutar la consulta
            $stmt->execute();

            // Confirmar transacción
            $pdo->commit();

            // Preparar la respuesta JSON
            $response = array('success' => true, 'message' => 'Movimiento registrado exitosamente');
        } catch (PDOException $e) {
            // Rollback en caso de error
            $pdo->rollBack();

            // Preparar la respuesta JSON de error
            $response = array('success' => false, 'message' => 'Error: ' . $e->getMessage());
        }

        // Devolver la respuesta como JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Si no es una solicitud POST válida
        header("HTTP/1.1 405 Method Not Allowed");
        exit;
    }
}


function obtenerMovimientos()
{
    $data = array();

    // Crear instancia de conexión
    $conexion = new ConexionPostgresql();
    $pdo = $conexion->obtenerConexion();

    // Validar y procesar los parámetros POST
    $limite = isset($_POST["limite"]) && is_numeric($_POST["limite"]) ? (int) $_POST["limite"] : 10;  // Definir un límite por defecto

    $query_buscado = "";

    // Si hay un texto de búsqueda
    if (isset($_POST["texto"]) && !empty($_POST["texto"])) {
        $textobuscado = htmlspecialchars($_POST["texto"]); // Evitar inyecciones XSS
        $query_buscado = " AND nombre LIKE :texto";  // Cambié "producto" a "nombre" para ser consistente con la estructura de datos
    }

    // Construir la consulta SQL
    $query = "SELECT * FROM vhistorial WHERE 1=1";  // Agregar "1=1" para facilitar la concatenación de condiciones
    $query_order = " ORDER BY id DESC";
    $query_limit = " LIMIT :limite";  // Usar un placeholder para el límite

    // Concatenar las condiciones de búsqueda
    $query .= $query_buscado . $query_order . $query_limit;

    // Preparar la consulta
    $stmtproductos = $pdo->prepare($query);

    // Bind del texto buscado si está presente
    if (!empty($query_buscado)) {
        $stmtproductos->bindValue(':texto', "%$textobuscado%", PDO::PARAM_STR);
    }

    // Bind del límite
    $stmtproductos->bindValue(':limite', $limite, PDO::PARAM_INT);

    // Ejecutar la consulta
    if ($stmtproductos->execute()) {
        $productos = $stmtproductos->fetchAll(PDO::FETCH_ASSOC);
        $data['productos'] = $productos;
    } else {
        echo json_encode(array('error' => 'Error al obtener los productos.'));
        return;
    }

    // Cerrar la conexión
    $conexion->cerrarConexion();

    // Devolver los datos en formato JSON
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

function guardarProductoNew()
{
    $accion = "Guardado";

    // Verificar si se recibió correctamente la solicitud POST
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Obtener los datos del producto desde AJAX
        $producto = $_POST['producto']; // Convertir JSON a array asociativo 

        // Validar que los campos requeridos no estén vacíos
        $camposObligatorios = ['estado', 'nombre', 'precio', 'descripcion'];
        foreach ($camposObligatorios as $campo) {
            if (empty($producto[$campo]) && $producto[$campo] !== '0') {
                // Devolver la respuesta con error si algún campo está vacío
                $response = array('success' => false, 'message' => "El campo '$campo' no puede estar vacío.");
                header('Content-Type: application/json');
                echo json_encode($response);
                exit; // Detener la ejecución si hay un campo vacío
            }
        }

        // Crear instancia de conexión
        $conexion = new ConexionPostgresql();
        $pdo = $conexion->obtenerConexion();

        try {
            $pdo->beginTransaction();

            // Verificar si es un producto nuevo o existente
            if (empty($producto['id'])) {
                // Insertar un nuevo producto
                $sql = "INSERT INTO producto (estado, nombre, precio, descripcion, stock) 
                        VALUES (:estado, :nombre, :precio, :descripcion, 0)";
            } else {
                // Actualizar producto existente
                $sql = "UPDATE producto SET estado = :estado, nombre = :nombre, 
                        precio = :precio, descripcion = :descripcion 
                        WHERE id = :codigo";
                $accion = "Actualizado";
            }

            // Preparar la consulta
            $stmt = $pdo->prepare($sql);

            // Bind de parámetros
            if (!empty($producto['id'])) {
                $stmt->bindValue(':codigo', $producto['id'], PDO::PARAM_INT);
            }
            $stmt->bindValue(':precio', $producto['precio'], PDO::PARAM_STR); // Considerar si es float
            $stmt->bindValue(':estado', $producto['estado'], PDO::PARAM_STR); // Booleano como texto en PostgreSQL === 'true' ? 't' : 'f'
            $stmt->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $producto['descripcion'], PDO::PARAM_STR);

            // Ejecutar la consulta
            $stmt->execute();

            // Confirmar transacción
            $pdo->commit();

            // Preparar la respuesta JSON
            $response = array('success' => true, 'message' => 'Producto ' . $accion . ' Exitosamente');
        } catch (PDOException $e) {
            // Rollback en caso de error
            $pdo->rollBack();

            // Preparar la respuesta JSON de error
            $response = array('success' => false, 'message' => 'Error: ' . $e->getMessage());
        }

        // Devolver la respuesta como JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Si no es una solicitud POST válida
        header("HTTP/1.1 405 Method Not Allowed");
        exit;
    }
}

function guardarProductoNew_()
{
    $accion = "Guardado";
    // Verificar si se recibió correctamente la solicitud POST
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Obtener los datos del producto desde AJAX
        $producto = $_POST['producto']; // Convertir JSON a array asociativo

        // Validar que los campos requeridos no estén vacíos
        $camposObligatorios = ['estado', 'nombre', 'precio', 'descripcion'];
        foreach ($camposObligatorios as $campo) {
            if (empty($producto[$campo]) && $producto[$campo] !== '0') {
                // Devolver la respuesta con error si algún campo está vacío
                $response = array('success' => false, 'message' => "El campo '$campo' no puede estar vacío.");
                header('Content-Type: application/json');
                echo json_encode($response);
                exit; // Detener la ejecución si hay un campo vacío
            }
        }

        // Crear instancia de conexión
        $conexion = new ConexionPostgresql();
        $pdo = $conexion->obtenerConexion();

        try {
            $pdo->beginTransaction();

            // Verificar si es un producto nuevo o existente
            if (empty($producto['codigo'])) {
                // Insertar un nuevo producto
                $sql = "INSERT INTO producto (estado, nombre, precio, descripcion, stock) 
                        VALUES (:estado, :producto,:precio, :descripcion, 0)";
            } else {
                // Actualizar producto existente
                $sql = "UPDATE producto SET estado = :estado, nombre = :producto, 
                        precio = :precio, descripcion = :descripcion 
                        WHERE id = :codigo";
                $accion = "Actualizado";
            }

            // Preparar la consulta
            $stmt = $pdo->prepare($sql);

            // Bind de parámetros
            if (!empty($producto['codigo'])) {
                $stmt->bindValue(':codigo', $producto['codigo'], PDO::PARAM_INT);
            }
            $stmt->bindValue(':precio', $producto['precio']);
            $stmt->bindValue(':estado', $producto['estado'], PDO::PARAM_BOOL);
            $stmt->bindValue(':producto', $producto['producto'], PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $producto['descripcion'], PDO::PARAM_STR);

            // Ejecutar la consulta
            $stmt->execute();

            // Confirmar transacción
            $pdo->commit();

            // Preparar la respuesta JSON
            $response = array('success' => true, 'message' => 'Producto ' . $accion . ' Exitosamente');
        } catch (PDOException $e) {
            // Rollback en caso de error
            $pdo->rollBack();

            // Preparar la respuesta JSON de error
            $response = array('success' => false, 'message' => 'Error: ' . $e->getMessage());
        }

        // Devolver la respuesta como JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Si no es una solicitud POST válida
        header("HTTP/1.1 405 Method Not Allowed");
        exit;
    }
}
function guardarProducto()
{
    // Verificar si se recibió correctamente la solicitud POST
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Obtener los datos del producto desde AJAX
        // print_r($_POST['producto']);
        // return 0;
        $producto = $_POST['producto']; // Convertir JSON a array asociativo

        // Crear instancia de conexión
        $conexion = new ConexionPostgresql();
        $pdo = $conexion->obtenerConexion();

        try {
            $pdo->beginTransaction();

            // Verificar si es un producto nuevo o existente
            if (empty($producto['codigo'])) {
                // Insertar un nuevo producto
                $sql = "INSERT INTO producto (estado, nombre, precio, descripcion) 
                        VALUES (:estado, :producto,:precio, :descripcion)";
            } else {
                // Actualizar producto existente
                $sql = "UPDATE producto estado = :estado, nombre = :producto, 
                        precio = :precio, descripcion = :descripcion 
                        WHERE id = :codigo";
            }

            // Preparar la consulta
            $stmt = $pdo->prepare($sql);

            // Bind de parámetros
            $stmt->bindValue(':codigo', $producto['codigo'], PDO::PARAM_INT);
            $stmt->bindValue(':estado', $producto['estado'], PDO::PARAM_BOOL);
            $stmt->bindValue(':producto', $producto['producto'], PDO::PARAM_STR);
            $stmt->bindValue(':precio', $producto['precio'], PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $producto['descripcion'], PDO::PARAM_STR);

            // Ejecutar la consulta
            $stmt->execute();

            // Confirmar transacción
            $pdo->commit();

            // Preparar la respuesta JSON
            $response = array('success' => true);
        } catch (PDOException $e) {
            // Rollback en caso de error
            $pdo->rollBack();

            // Preparar la respuesta JSON de error
            $response = array('success' => false, 'message' => 'Error: ' . $e->getMessage());
        }

        // Devolver la respuesta como JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Si no es una solicitud POST válida
        header("HTTP/1.1 405 Method Not Allowed");
        exit;
    }
}

function obtenerProductos()
{
    $data = array();

    // Crear instancia de conexión
    $conexion = new ConexionPostgresql();
    $pdo = $conexion->obtenerConexion();

    // Validar y procesar los parámetros POST
    $limite = isset($_POST["limite"]) && is_numeric($_POST["limite"]) ? (int) $_POST["limite"] : 10;  // Definir un límite por defecto

    $query_buscado = "";

    // Si hay un texto de búsqueda
    if (isset($_POST["texto"]) && !empty($_POST["texto"])) {
        $textobuscado = htmlspecialchars($_POST["texto"]); // Evitar inyecciones XSS
        $query_buscado = " AND nombre LIKE :texto";  // Cambié "producto" a "nombre" para ser consistente con la estructura de datos
    }

    // Construir la consulta SQL
    $query = "SELECT * FROM producto WHERE 1=1";  // Agregar "1=1" para facilitar la concatenación de condiciones
    $query_order = " ORDER BY id DESC";
    $query_limit = " LIMIT :limite";  // Usar un placeholder para el límite

    // Concatenar las condiciones de búsqueda
    $query .= $query_buscado . $query_order . $query_limit;

    // Preparar la consulta
    $stmtproductos = $pdo->prepare($query);

    // Bind del texto buscado si está presente
    if (!empty($query_buscado)) {
        $stmtproductos->bindValue(':texto', "%$textobuscado%", PDO::PARAM_STR);
    }

    // Bind del límite
    $stmtproductos->bindValue(':limite', $limite, PDO::PARAM_INT);

    // Ejecutar la consulta
    if ($stmtproductos->execute()) {
        $productos = $stmtproductos->fetchAll(PDO::FETCH_ASSOC);
        $data['productos'] = $productos;
    } else {
        echo json_encode(array('error' => 'Error al obtener los productos.'));
        return;
    }

    // Cerrar la conexión
    $conexion->cerrarConexion();

    // Devolver los datos en formato JSON
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

function obtenerProductos_()
{
    $data = array();
    $conexion = new ConexionPostgresql();
    $pdo = $conexion->obtenerConexion();
    $query_filter = "";
    $query_buscado = "";
    $limite = $_POST["limite"];

    if (isset($_POST["texto"]) && !empty($_POST["texto"])) {
        $textobuscado = htmlspecialchars($_POST["texto"]); // Evitar inyecciones XSS
        $query_buscado = " AND producto LIKE :texto";
    }


    // Obtener productos
    $query = "SELECT * FROM producto";
    $query_order = " ORDER BY codigo DESC";
    $query_limit = " LIMIT " . $limite;

    if (!empty($query_filter) || !empty($query_buscado)) {
        $query .= " WHERE " . $query_filter . $query_buscado;
    }

    $query .= $query_order . $query_limit;
    $stmtproductos = $pdo->prepare($query);

    if (!empty($query_buscado)) {
        $stmtproductos->bindValue(':texto', "%$textobuscado%", PDO::PARAM_STR);
    }

    if ($stmtproductos->execute()) {
        $productos = $stmtproductos->fetchAll(PDO::FETCH_ASSOC);
        $data['productos'] = $productos;
    } else {
        echo json_encode(array('error' => 'Error al obtener los productos.'));
        return;
    }

    $conexion->cerrarConexion();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}
