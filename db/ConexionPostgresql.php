<?php
class ConexionPostgresql {
    private $conexion;

    public function __construct() {
        // Cargar las variables de entorno desde el archivo .env
        $host = "localhost";
        $db = "inventario_tienda";
        $user = "postgres";
        $pass = "postgresql";

        $dsn = "pgsql:host=" . $host . ";dbname=" . $db;

        try {
            $this->conexion = new PDO($dsn, $user, $pass);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    public function obtenerConexion() {
        return $this->conexion;
    }

    public function cerrarConexion() {
        $this->conexion = null;
    }
}
?>
