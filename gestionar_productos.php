<?php
require_once 'config_sesion.php';
require_once 'verificar_sesion.php';
require_once 'conexion.php';

$mensaje = "";
$tipo_mensaje = "";

// ==============
// insertar productos formulario
// ==============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'insertar_producto') {
    
    // Obtener y limpiar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    
    // Validaciones mediante PHP
    $errores = [];
    if (empty($nombre)) $errores[] = "El nombre es obligatorio";
    if (strlen($nombre) > 100) $errores[] = "El nombre no puede exceder 100 caracteres";
    if ($precio <= 0) $errores[] = "El precio debe ser mayor a 0";
    if ($stock < 0) $errores[] = "El stock no puede ser negativo";
    
    if (empty($errores)) {
        try {
            $db = conectarDB();
            $stmt = $db->prepare("INSERT INTO PRODUCTO (nombre, descripcion, precio, stock) VALUES (:nombre, :descripcion, :precio, :stock)");
            $stmt->execute([
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':precio' => $precio,
                ':stock' => $stock
            ]);
            $mensaje = "Producto '$nombre' registrado exitosamente con ID: " . $db->lastInsertId();
            $tipo_mensaje = "success";
        } catch (PDOException $e) {
            $mensaje = "Error al registrar: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "" . implode(", ", $errores);
        $tipo_mensaje = "error";
    }
}

// ==============
// Obtener listado de productos
// ==============
$productos = [];
try {
    $db = conectarDB();
    $stmt = $db->query("SELECT * FROM PRODUCTO ORDER BY id_producto DESC");
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $mensaje = "Error al cargar productos: " . $e->getMessage();
    $tipo_mensaje = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Productos - Tienda</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { max-width: 900px; margin: 30px auto; }
        .form-box, .table-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .btn-submit { width: 100%; padding: 12px; background: #0078d7; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background: #005db1; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0078d7; color: white; }
        tr:hover { background: #f5f5f5; }
        .stock-bajo { color: #dc3545; font-weight: bold; }
        .stock-ok { color: #28a745; }
        .nav-links { margin-bottom: 20px; }
        .nav-links a { color: #0078d7; text-decoration: none; margin-right: 15px; }
        .nav-links a:hover { text-decoration: underline; }
        .precio { font-weight: bold; color: #28a745; }
        .error-input { border-color: #dc3545 !important; background: #fff5f5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="index.php">← Volver a la Tienda</a>
            <a href="gestionar_clientes.php">Gestionar Clientes →</a>
            <a href="gestionar_compras.php">Gestionar Compras →</a>
            <a href="gestionar_pedidos.php">Gestionar Pedidos →</a>
            <a href="reporte_clientes.php">Reporte Avanzado →</a>
        </div>
        
        <h1 style="text-align:center; color:#333; margin-bottom:20px;">Gestionar Productos</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <!-- insertar productos formulario-->
        <div class="form-box">
            <h2>Nuevo Producto</h2>
            <form method="POST" action="" id="formProducto" onsubmit="return validarProducto()">
                <input type="hidden" name="accion" value="insertar_producto">
                
                <div class="form-group">
                    <label for="nombre">Nombre del Producto *</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100" 
                           placeholder="Ej: Notebook HP G4">
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" maxlength="500"
                              placeholder="Describe las características del producto..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="precio">Precio (CLP) *</label>
                    <input type="number" id="precio" name="precio" required min="1" step="0.01"
                           placeholder="Ej: 550000">
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock Disponible *</label>
                    <input type="number" id="stock" name="stock" required min="0" 
                           placeholder="Ej: 10">
                </div>
                
                <button type="submit" class="btn-submit">Guardar Producto</button>
            </form>
        </div>
        
        <!-- tabla de producto -->
        <div class="table-box">
            <h2>Lista de Productos</h2>
            <?php if (count($productos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripcion</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $prod): ?>
                        <tr>
                            <td><?php echo $prod['id_producto']; ?></td>
                            <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                            <td><?php echo htmlspecialchars(substr($prod['descripcion'] ?? '', 0, 50)) . '...'; ?></td>
                            <td class="precio">$<?php echo number_format($prod['precio'], 0, ',', '.'); ?></td>
                            <td><?php echo $prod['stock']; ?></td>
                            <td class="<?php echo $prod['stock'] < 5 ? 'stock-bajo' : 'stock-ok'; ?>">
                                <?php echo $prod['stock'] < 5 ? 'Stock Bajo' : 'Disponible'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#666;">No hay productos registrados.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- validacion con javascript -->
    <script>
    function validarProducto() {
        let nombre = document.getElementById('nombre').value.trim();
        let precio = parseFloat(document.getElementById('precio').value);
        let stock = parseInt(document.getElementById('stock').value);
        let errores = [];
        
        // Resetear estilos
        document.querySelectorAll('.error-input').forEach(el => el.classList.remove('error-input'));
        
        if (nombre.length < 2) {
            errores.push("El nombre debe tener al menos 2 caracteres");
            document.getElementById('nombre').classList.add('error-input');
        }
        if (isNaN(precio) || precio <= 0) {
            errores.push("El precio debe ser mayor a 0");
            document.getElementById('precio').classList.add('error-input');
        }
        if (isNaN(stock) || stock < 0) {
            errores.push("El stock no puede ser negativo");
            document.getElementById('stock').classList.add('error-input');
        }
        
        if (errores.length > 0) {
            alert("Errores de validacion:\\n• " + errores.join("\\n• "));
            return false;
        }
        return true;
    }
    </script>
</body>
</html>