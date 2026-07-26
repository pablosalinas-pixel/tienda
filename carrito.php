<?php
require_once 'config_sesion.php';
require_once 'conexion.php';
header('Content-Type: application/json');

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        $id = intval($_POST['id'] ?? 0);
        
        try {
            $db = conectarDB();
            
            // Productos desde sql
            $stmt = $db->prepare("SELECT id_producto, nombre, precio, stock FROM PRODUCTO WHERE id_producto = :id");
            $stmt->execute([':id' => $id]);
            $producto = $stmt->fetch();
            
            if (!$producto) {
                echo json_encode(['success' => false, 'mensaje' => 'Producto no encontrado']);
                exit();
            }
            
            if ($producto['stock'] <= 0) {
                echo json_encode(['success' => false, 'mensaje' => 'Producto agotado']);
                exit();
            }
            
            // Valida cantidad en carrito
            $cantidad_en_carrito = 0;
            foreach ($_SESSION['carrito'] as $item) {
                if ($item['id'] == $id) {
                    $cantidad_en_carrito = $item['cantidad'];
                    break;
                }
            }
            
            // Valida stock disponible
            if ($cantidad_en_carrito >= $producto['stock']) {
                echo json_encode(['success' => false, 'mensaje' => 'Stock insuficiente. Disponible: ' . $producto['stock']]);
                exit();
            }
            
            // Agregar al carrito
            $en_carrito = false;
            foreach ($_SESSION['carrito'] as &$item) {
                if ($item['id'] == $id) {
                    $item['cantidad']++;
                    $en_carrito = true;
                    break;
                }
            }
            
            if (!$en_carrito) {
                $_SESSION['carrito'][] = [
                    'id' => $id,
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'cantidad' => 1
                ];
            }
            
            echo json_encode(['success' => true, 'mensaje' => $producto['nombre'] . ' agregado al carrito']);
            
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'mensaje' => 'Error de base de datos']);
        }
        break;

    case 'obtener':
        $total = 0;
        $cantidad = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $total += $item['precio'] * $item['cantidad'];
            $cantidad += $item['cantidad'];
        }
        echo json_encode([
            'success' => true,
            'carrito' => $_SESSION['carrito'],
            'total_items' => $cantidad,
            'total_precio' => $total
        ]);
        break;

    case 'eliminar':
        $id = intval($_POST['id'] ?? 0);
        foreach ($_SESSION['carrito'] as $key => $item) {
            if ($item['id'] == $id) {
                unset($_SESSION['carrito'][$key]);
                $_SESSION['carrito'] = array_values($_SESSION['carrito']);
                break;
            }
        }
        echo json_encode(['success' => true, 'mensaje' => 'Producto eliminado']);
        break;

    case 'vaciar':
        $_SESSION['carrito'] = [];
        echo json_encode(['success' => true, 'mensaje' => 'Carrito vaciado']);
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Proceso no valido']);
}
?>