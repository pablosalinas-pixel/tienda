<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tienda');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function conectarDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $opciones = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        );
        $conexion = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        return $conexion;
    } catch (PDOException $e) {
        error_log("Error de conexion MySQL: " . $e->getMessage());
        die("Error al conectar con la base de datos.");
    }
}
?>