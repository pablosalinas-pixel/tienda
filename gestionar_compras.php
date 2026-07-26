<?php
require_once 'config_sesion.php';
require_once 'verificar_sesion.php';
require_once 'conexion.php';

$mensaje = "";
$tipo_mensaje = "";

// ==============
// insertar compras
// ==============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'insertar_compra') {
    
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $id_cliente = intval($_POST['id_cliente'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 0);
    
    $errores = [];
    if ($id_producto <= 0) $errores[] = "Debe seleccionar un producto";
    if ($id_cliente <= 0) $errores[] = "Debe seleccionar un cliente";
    if ($cantidad <= 0) $errores[] = "La cantidad debe ser mayor a 0";
    
    if (empty($errores)) {
        try {
            $db = conectarDB();
            $db->beginTransaction();
            
            // validacion disponible con join
            $stmt = $db->prepare("
                SELECT p.id_producto, p.nombre, p.precio, p.stock,
                       CASE 
                           WHEN p.stock >= :cantidad THEN 'Disponible'
                           WHEN p.stock > 0 THEN 'Stock Insuficiente'
                           ELSE 'Agotado'
                       END as estado,
                       p.stock - :cantidad2 as stock_restante
                FROM PRODUCTO p
                WHERE p.id_producto = :id_producto
            ");
            $stmt->execute([
                ':cantidad' => $cantidad,
                ':cantidad2' => $cantidad,
                ':id_producto' => $id_producto
            ]);
            $producto = $stmt->fetch();
            
            if (!$producto) {
                throw new Exception("Producto no encontrado");
            }
            
            if ($producto['stock'] < $cantidad) {
                throw new Exception("Stock insuficiente. Disponible: " . $producto['stock'] . ", Solicitado: " . $cantidad);
            }
            
            $total = $producto['precio'] * $cantidad;
            
            // INSERTAR COMPRA
            $stmt = $db->prepare("INSERT INTO COMPRA (cantidad, total, id_producto, id_cliente) VALUES (:cantidad, :total, :id_producto, :id_cliente)");
            $stmt->execute([
                ':cantidad' => $cantidad,
                ':total' => $total,
                ':id_producto' => $id_producto,
                ':id_cliente' => $id_cliente
            ]);
            
            // ACTUALIZAR STOCK
            $stmt = $db->prepare("UPDATE PRODUCTO SET stock = stock - :cantidad WHERE id_producto = :id_producto");
            $stmt->execute([
                ':cantidad' => $cantidad,
                ':id_producto' => $id_producto
            ]);
            
            $db->commit();
            $mensaje = "Compra registrada exitosamente. Total: $" . number_format($total, 0, ',', '.');
            $tipo_mensaje = "success";
            
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            $mensaje = "" . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "" . implode(", ", $errores);
        $tipo_mensaje = "error";
    }
}

// ==============
// Datos para formularios
// ==============
$productos = [];
$clientes = [];
$compras = [];

try {
    $db = conectarDB();
    
    // Productos con disponibilidad
    $stmt = $db->query("SELECT id_producto, nombre, precio, stock FROM PRODUCTO WHERE stock > 0 ORDER BY nombre");
    $productos = $stmt->fetchAll();
    
    // Clientes
    $stmt = $db->query("SELECT id_cliente, nombre FROM CLIENTE ORDER BY nombre");
    $clientes = $stmt->fetchAll();
    
    // Compras con JOIN para mostrar nombres
    $stmt = $db->query("
        SELECT c.id_compra, c.cantidad, c.total, c.fecha,
               p.nombre as producto_nombre, p.precio as producto_precio,
               cl.nombre as cliente_nombre
        FROM COMPRA c
        INNER JOIN PRODUCTO p ON c.id_producto = p.id_producto
        INNER JOIN CLIENTE cl ON c.id_cliente = cl.id_cliente
        ORDER BY c.fecha DESC
    ");
    $compras = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $mensaje = "Error de base de datos: " . $e->getMessage();
    $tipo_mensaje = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Compras - Tienda</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { max-width: 1000px; margin: 30px auto; }
        .form-box, .table-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
        .btn-submit { width: 100%; padding: 12px; background: #ff6b00; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background: #e65c00; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #ff6b00; color: white; }
        tr:hover { background: #f5f5f5; }
        .nav-links { margin-bottom: 20px; }
        .nav-links a { color: #0078d7; text-decoration: none; margin-right: 15px; }
        .nav-links a:hover { text-decoration: underline; }
        .disponible { color: #28a745; font-weight: bold; }
        .agotado { color: #dc3545; font-weight: bold; }
        .stock-info { background: #e7f3ff; padding: 10px; border-radius: 6px; margin-top: 10px; font-size: 14px; }
        .precio { font-weight: bold; color: #28a745; }
        .total { font-weight: bold; color: #ff6b00; font-size: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="index.php">← Volver a la Tienda</a>
            <a href="gestionar_productos.php">← Productos</a>
            <a href="gestionar_clientes.php">← Clientes</a>
            <a href="gestionar_pedidos.php">Gestionar Pedidos →</a>
            <a href="reporte_clientes.php">Reporte Avanzado →</a>
        </div>
        
        <h1 style="text-align:center; color:#333; margin-bottom:20px;">Gestionar Compras</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <!-- Insertar compra formulario -->
        <div class="form-box">
            <h2>Nueva Compra</h2>
            <form method="POST" action="" id="formCompra" onsubmit="return validarCompra()">
                <input type="hidden" name="accion" value="insertar_compra">
                
                <div class="form-group">
                    <label for="id_cliente">Cliente *</label>
                    <select id="id_cliente" name="id_cliente" required>
                        <option value="">-- Seleccione un cliente --</option>
                        <?php foreach ($clientes as $cli): ?>
                        <option value="<?php echo $cli['id_cliente']; ?>">
                            <?php echo htmlspecialchars($cli['nombre']); ?> (ID: <?php echo $cli['id_cliente']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_producto">Producto *</label>
                    <select id="id_producto" name="id_producto" required onchange="actualizarInfoProducto()">
                        <option value="">-- Seleccione un producto --</option>
                        <?php foreach ($productos as $prod): ?>
                        <option value="<?php echo $prod['id_producto']; ?>" 
                                data-precio="<?php echo $prod['precio']; ?>"
                                data-stock="<?php echo $prod['stock']; ?>">
                            <?php echo htmlspecialchars($prod['nombre']); ?> - $<?php echo number_format($prod['precio'], 0, ',', '.'); ?> (Stock: <?php echo $prod['stock']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="info-producto" class="stock-info" style="display:none;">
                    <strong>Informacion del Producto:</strong><br>
                    Precio unitario: $<span id="precio-unitario">0</span><br>
                    Stock disponible: <span id="stock-disponible">0</span> unidades
                </div>
                
                <div class="form-group">
                    <label for="cantidad">Cantidad *</label>
                    <input type="number" id="cantidad" name="cantidad" required min="1" 
                           placeholder="Ej: 2" onchange="calcularTotal()" onkeyup="calcularTotal()">
                </div>
                
                <div class="stock-info" style="background: #fff3e0;">
                    <strong>Total Estimado:</strong> <span id="total-estimado" class="total">$0</span>
                </div>
                
                <br>
                <button type="submit" class="btn-submit">Confirmar Compra</button>
            </form>
        </div>
        
        <!-- Tacla de compras -->
        <div class="table-box">
            <h2>Historial de Compras</h2>
            <?php if (count($compras) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Compra</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras as $comp): ?>
                        <tr>
                            <td><?php echo $comp['id_compra']; ?></td>
                            <td><?php echo htmlspecialchars($comp['cliente_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($comp['producto_nombre']); ?></td>
                            <td><?php echo $comp['cantidad']; ?></td>
                            <td class="precio">$<?php echo number_format($comp['producto_precio'], 0, ',', '.'); ?></td>
                            <td class="total">$<?php echo number_format($comp['total'], 0, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($comp['fecha'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#666;">No se encontrario compras registradas.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Calculos y validacion con javascript  -->
    <script>
    function actualizarInfoProducto() {
        let select = document.getElementById('id_producto');
        let option = select.options[select.selectedIndex];
        let infoDiv = document.getElementById('info-producto');
        
        if (select.value === "") {
            infoDiv.style.display = 'none';
            return;
        }
        
        let precio = parseFloat(option.getAttribute('data-precio'));
        let stock = parseInt(option.getAttribute('data-stock'));
        
        document.getElementById('precio-unitario').textContent = precio.toLocaleString('es-CL');
        document.getElementById('stock-disponible').textContent = stock;
        infoDiv.style.display = 'block';
        
        calcularTotal();
    }
    
    function calcularTotal() {
        let select = document.getElementById('id_producto');
        let option = select.options[select.selectedIndex];
        let precio = parseFloat(option.getAttribute('data-precio') || 0);
        let cantidad = parseInt(document.getElementById('cantidad').value || 0);
        let stock = parseInt(option.getAttribute('data-stock') || 0);
        
        let total = precio * cantidad;
        document.getElementById('total-estimado').textContent = '$' + total.toLocaleString('es-CL');
        
        // Validar stock
        if (cantidad > stock) {
            document.getElementById('cantidad').style.borderColor = '#dc3545';
        } else {
            document.getElementById('cantidad').style.borderColor = '#ccc';
        }
    }
    
    function validarCompra() {
        let id_cliente = document.getElementById('id_cliente').value;
        let id_producto = document.getElementById('id_producto').value;
        let cantidad = parseInt(document.getElementById('cantidad').value || 0);
        let select = document.getElementById('id_producto');
        let option = select.options[select.selectedIndex];
        let stock = parseInt(option.getAttribute('data-stock') || 0);
        
        let errores = [];
        
        if (!id_cliente) errores.push("Seleccione un cliente");
        if (!id_producto) errores.push("Seleccione un producto");
        if (cantidad <= 0) errores.push("La cantidad debe ser mayor a 0");
        if (cantidad > stock) errores.push("Stock insuficiente. Máximo: " + stock);
        
        if (errores.length > 0) {
            alert("Errores de validacion:\\n• " + errores.join("\\n• "));
            return false;
        }
        return true;
    }
    </script>
</body>
</html>