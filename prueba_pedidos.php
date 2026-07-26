<?php
/**
 * prueba_pedidos.php
 * Script para probar las nuevas funcionalidades de Pedido v2.0
 * 
 * IMPORTANTE: Este archivo debe estar en C:\xampp\htdocs\tienda\prueba_pedidos.php
 * Acceso: http://localhost/tienda/prueba_pedidos.php
 */

// Activar errores para ver que pasa
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html lang='es'>\n<head>\n";
echo "<meta charset='UTF-8'>\n<title>Pruebas - Pedido v2.0</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f5f5f5; }\n";
echo ".test-box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }\n";
echo ".success { color: #28a745; font-weight: bold; }\n";
echo ".error { color: #dc3545; font-weight: bold; }\n";
echo ".info { color: #0078d7; }\n";
echo "pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }\n";
echo "table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 14px; }\n";
echo "th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }\n";
echo "th { background: #0078d7; color: white; }\n";
echo "tr:hover { background: #f5f5f5; }\n";
echo ".badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; color: white; }\n";
echo ".badge-green { background: #28a745; }\n";
echo ".badge-red { background: #dc3545; }\n";
echo ".badge-blue { background: #0078d7; }\n";
echo "</style>\n</head>\n<body>\n";

echo "<h1>Pruebas del Sistema de Pedidos v2.0</h1>\n";

// ============================================
// PRUEBA 0: Verificar que archivos existen
// ============================================
echo "<div class='test-box'>\n";
echo "<h2>Prueba 0: Verificar Archivos del Proyecto</h2>\n";

$archivos_requeridos = [
    'conexion.php',
    'Pedido.php',
    'config_sesion.php',
    'verificar_sesion.php'
];

$todo_ok = true;
foreach ($archivos_requeridos as $archivo) {
    $existe = file_exists($archivo);
    $icono = $existe ? "<span class='badge badge-green'>OK</span>" : "<span class='badge badge-red'>FALTA</span>";
    echo "<p>$icono $archivo</p>\n";
    if (!$existe) $todo_ok = false;
}

if (!$todo_ok) {
    echo "<p class='error'>Faltan archivos. Verifica que estan en la misma carpeta que este archivo.</p>\n";
    echo "<p class='info'>Ruta actual: " . __DIR__ . "</p>\n";
}
echo "</div>\n";

if (!$todo_ok) {
    echo "</body></html>";
    exit;
}

// ============================================
// PRUEBA 1: Conexion a Base de Datos
// ============================================
echo "<div class='test-box'>\n";
echo "<h2>Prueba 1: Conexion a Base de Datos</h2>\n";

try {
    require_once 'conexion.php';
    $db = conectarDB();
    echo "<p class='success'>Conexion a MySQL exitosa</p>\n";

    // Verificar tablas
    $stmt = $db->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<p>Tablas encontradas:</p>\n";
    echo "<table><tr><th>Tabla</th><th>Estado</th></tr>\n";

    $tablas_esperadas = ['CLIENTE', 'PRODUCTO', 'COMPRA', 'PEDIDO', 'TRANSACCION_PAGO'];
    foreach ($tablas_esperadas as $tabla) {
        $existe = in_array($tabla, $tablas);
        $estado = $existe ? "<span class='badge badge-green'>EXiste</span>" : "<span class='badge badge-red'>NO EXISTE</span>";
        echo "<tr><td>$tabla</td><td>$estado</td></tr>\n";
    }
    echo "</table>\n";

} catch (PDOException $e) {
    echo "<p class='error'>Error de conexion: " . $e->getMessage() . "</p>\n";
    echo "</body></html>";
    exit;
}
echo "</div>\n";

// ============================================
// PRUEBA 2: Cargar clase Pedido (original)
// ============================================
echo "<div class='test-box'>\n";
echo "<h2>Prueba 2: Cargar Clase Pedido</h2>\n";

try {
    require_once 'Pedido.php';

    // Verificar que la clase existe
    if (!class_exists('Pedido')) {
        throw new Exception("La clase Pedido no se cargo correctamente");
    }

    echo "<p class='success'>Clase Pedido cargada correctamente</p>\n";

    // Crear un pedido de prueba
    $pedido = new Pedido(
        'Pedido de prueba desde sistema v2.0',
        'Express',
        'Notebook HP G4',
        3,
        'Pedido de prueba para validar sistema'
    );

    echo "<p class='info'>Pedido creado en memoria:</p>\n";
    echo "<pre>";
    echo "Descripcion: " . $pedido->descripcionPedido . "\n";
    echo "Tipo: " . $pedido->tipoPedido . "\n";
    echo "Producto: " . $pedido->producto . "\n";
    echo "Unidades: " . $pedido->unidades . "\n";
    echo "Estado: " . $pedido->estado . "\n";
    echo "Fecha: " . $pedido->fecha . "\n";
    echo "</pre>\n";

    echo "<p class='success'>Metodo mostrarInfo(): " . $pedido->mostrarInfo() . "</p>\n";

} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>\n";
}
echo "</div>\n";

// ============================================
// PRUEBA 3: Verificar tabla PEDIDO en BD
// ============================================
echo "<div class='test-box'>\n";
echo "<h2>Prueba 3: Verificar Tabla PEDIDO en Base de Datos</h2>\n";

try {
    // Verificar si la tabla PEDIDO existe
    $stmt = $db->query("SHOW TABLES LIKE 'PEDIDO'");
    $existe_pedido = $stmt->rowCount() > 0;

    if ($existe_pedido) {
        echo "<p class='success'>Tabla PEDIDO existe en la base de datos</p>\n";

        // Ver estructura
        $stmt = $db->query("DESCRIBE PEDIDO");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<p>Estructura de la tabla:</p>\n";
        echo "<table>\n";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th><th>Extra</th></tr>\n";
        foreach ($columnas as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . $col['Extra'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";

        // Ver datos
        $stmt = $db->query("SELECT * FROM PEDIDO ORDER BY fecha DESC");
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<p class='info'>Total de pedidos registrados: " . count($pedidos) . "</p>\n";

        if (count($pedidos) > 0) {
            echo "<table>\n";
            echo "<tr><th>ID</th><th>Producto</th><th>Unidades</th><th>Tipo</th><th>Estado</th><th>Fecha</th></tr>\n";
            foreach ($pedidos as $p) {
                echo "<tr>";
                echo "<td>" . ($p['id_pedido'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($p['producto'] ?? '') . "</td>";
                echo "<td>" . ($p['unidades'] ?? '') . "</td>";
                echo "<td>" . ($p['tipo'] ?? '') . "</td>";
                echo "<td>" . ($p['estado'] ?? '') . "</td>";
                echo "<td>" . ($p['fecha'] ?? '') . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }

    } else {
        echo "<p class='error'>Tabla PEDIDO NO existe. Debes crearla primero.</p>\n";
        echo "<p class='info'>Ejecuta el SQL del archivo schema_tabla_PEDIDO.sql en PHPMyAdmin</p>\n";
    }

} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>\n";
}
echo "</div>\n";

// ============================================
// PRUEBA 4: Verificar tabla TRANSACCION_PAGO
// ============================================
echo "<div class='test-box'>\n";
echo "<h2>Prueba 4: Verificar Tabla TRANSACCION_PAGO</h2>\n";

try {
    $stmt = $db->query("SHOW TABLES LIKE 'TRANSACCION_PAGO'");
    $existe = $stmt->rowCount() > 0;

    if ($existe) {
        echo "<p class='success'>Tabla TRANSACCION_PAGO existe</p>\n";

        $stmt = $db->query("DESCRIBE TRANSACCION_PAGO");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table>\n";
        echo "<tr><th>Campo</th><th>Tipo</th></tr>\n";
        foreach ($columnas as $col) {
            echo "<tr><td>" . $col['Field'] . "</td><td>" . $col['Type'] . "</td></tr>\n";
        }
        echo "</table>\n";

    } else {
        echo "<p class='error'>Tabla TRANSACCION_PAGO NO existe</p>\n";
        echo "<p class='info'>Ejecuta el SQL del archivo schema_tabla_TRANSACCION_PAGO.sql</p>\n";
    }

} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>\n";
}
echo "</div>\n";

// ============================================
// PRUEBA 5: Probar insercion manual en PEDIDO
// ============================================
echo "<div class='test-box'>\n";
echo "<h2>Prueba 5: Insertar Pedido de Prueba en BD</h2>\n";

try {
    // Verificar si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'PEDIDO'");
    if ($stmt->rowCount() == 0) {
        echo "<p class='error'>No se puede insertar: tabla PEDIDO no existe</p>\n";
        echo "</div>\n";
    } else {
        // Insertar un pedido de prueba directamente
        $stmt = $db->prepare("
            INSERT INTO PEDIDO (descripcion, tipo, producto, unidades, observaciones, estado, id_cliente) 
            VALUES (:desc, :tipo, :prod, :uni, :obs, :estado, :id_cli)
        ");

        $resultado = $stmt->execute([
            ':desc' => 'Pedido insertado desde prueba_pedidos.php',
            ':tipo' => 'Normal',
            ':prod' => 'Monitor Sony 27\"',
            ':uni' => 2,
            ':obs' => 'Pedido de prueba automatico',
            ':estado' => 'Pendiente',
            ':id_cli' => 1
        ]);

        if ($resultado) {
            $id_insertado = $db->lastInsertId();
            echo "<p class='success'>Pedido insertado correctamente con ID: $id_insertado</p>\n";
        } else {
            echo "<p class='error'>Error al insertar pedido</p>\n";
        }
    }

} catch (PDOException $e) {
    // Si el error es por FK (cliente no existe)
    if ($e->getCode() == 23000) {
        echo "<p class='error'>Error: El cliente con ID 1 no existe en la tabla CLIENTE</p>\n";
        echo "<p class='info'>Solucion: Inserta un cliente primero o usa id_cliente = NULL</p>\n";
    } else {
        echo "<p class='error'>Error PDO: " . $e->getMessage() . "</p>\n";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>\n";
}
echo "</div>\n";

// ============================================
// PRUEBA 6: Verificar tabla CLIENTE
// ============================================
echo "<div class='test-box'>\n";
echo "<h2>Prueba 6: Verificar Datos en Tabla CLIENTE</h2>\n";

try {
    $stmt = $db->query("SELECT * FROM CLIENTE LIMIT 5");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p class='info'>Total de clientes: " . count($clientes) . "</p>\n";

    if (count($clientes) > 0) {
        echo "<table>\n";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th></tr>\n";
        foreach ($clientes as $c) {
            echo "<tr>";
            echo "<td>" . $c['id_cliente'] . "</td>";
            echo "<td>" . htmlspecialchars($c['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($c['email']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p class='error'>No hay clientes registrados. Registra algunos en gestionar_clientes.php</p>\n";
    }

} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>\n";
}
echo "</div>\n";

// ============================================
// RESUMEN FINAL
// ============================================
echo "<div class='test-box' style='background: #e8f5e9;'>\n";
echo "<h2>Resumen de Pruebas</h2>\n";
echo "<ul>\n";
echo "<li>Verificacion de archivos del proyecto</li>\n";
echo "<li>Conexion a MySQL/MariaDB</li>\n";
echo "<li>Carga de clase Pedido</li>\n";
echo "<li>Verificacion de tablas PEDIDO y TRANSACCION_PAGO</li>\n";
echo "<li>Insercion de pedido de prueba</li>\n";
echo "<li>Verificacion de clientes</li>\n";
echo "</ul>\n";
echo "<p><strong>Si todas las pruebas muestran verde, el sistema esta listo</strong></p>\n";
echo "<p class='info'>Ruta de este archivo: " . __FILE__ . "</p>\n";
echo "</div>\n";

echo "</body>\n</html>";
?>
