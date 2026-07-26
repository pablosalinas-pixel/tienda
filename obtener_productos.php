<?php
require_once 'conexion.php';
header('Content-Type: application/json');

try {
    $db = conectarDB();
    $stmt = $db->query("SELECT id_producto, nombre, precio, stock FROM PRODUCTO ORDER BY id_producto");
    $productos = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'productos' => $productos
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al cargar productos'
    ]);
}
?>