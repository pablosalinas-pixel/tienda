<?php
require_once 'verificar_sesion.php';
require_once 'conexion.php';       // NUEVO: Conexion MySQL
require_once 'Pedido.php';         // Ahora carga la version mejorada

// ==============
// Seguridad XSS
// ==============
$descripcion = htmlspecialchars($_POST['descripcion'] ?? '');
$tipo = htmlspecialchars($_POST['tipo'] ?? '');
$producto = htmlspecialchars($_POST['producto'] ?? '');
$unidades = intval($_POST['unidades'] ?? 1);
$observaciones = htmlspecialchars($_POST['observaciones'] ?? '');

// NUEVO: Obtener ID del cliente desde la sesion
$id_cliente = $_SESSION['usuario_id'] ?? null;

// Crear objeto Pedido con id_cliente (6to parametro)
$miPedido = new Pedido($descripcion, $tipo, $producto, $unidades, $observaciones, $id_cliente);

// ==============
// Guardar en MySQL
// ==============
$guardado_db = false;
$id_pedido_db = null;

try {
    $db = conectarDB();
    $guardado_db = $miPedido->guardarEnDB($db);
    
    if ($guardado_db) {
        $id_pedido_db = $db->lastInsertId();
    }
    
} catch (Exception $e) {
    error_log("[procesar.php] Error al guardar en BD: " . $e->getMessage());
}

// ==============
// LEGACY: Mantener compatibilidad con archivos .txt
// ==============
$pedidos = [];
if (file_exists('pedidos.txt')) {
    $pedidos = json_decode(file_get_contents('pedidos.txt'), true) ?: [];
}
$pedidos[] = [
    'descripcion' => $miPedido->descripcionPedido,
    'tipo' => $miPedido->tipoPedido,
    'producto' => $miPedido->producto,
    'unidades' => $miPedido->unidades,
    'observaciones' => $miPedido->observaciones,
    'estado' => $miPedido->estado,
    'fecha' => $miPedido->fecha,
    'usuario' => $_SESSION['usuario_nombre'],
    'id_cliente' => $miPedido->id_cliente,
    'id_pedido_db' => $id_pedido_db,
    'guardado_en_bd' => $guardado_db
];
file_put_contents('pedidos.txt', json_encode($pedidos, JSON_PRETTY_PRINT));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido Registrado</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .success-box { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error-box { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .info-box { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .badge-mysql { background: #0078d7; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        .badge-legacy { background: #6c757d; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
    <div style="max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
        <h1>Pedido Registrado</h1>
        
        <?php if ($guardado_db && $id_pedido_db): ?>
            <div class="success-box">
                <strong>Pedido guardado en base de datos</strong><br>
                <span class="badge-mysql">MySQL</span>
                <p style="margin-top: 10px;">Numero de pedido: <strong>#<?php echo $id_pedido_db; ?></strong></p>
            </div>
        <?php else: ?>
            <div class="error-box">
                <strong>Advertencia:</strong> El pedido se guardo en archivo local pero no en la base de datos.<br>
                <span class="badge-legacy">Modo Legacy</span>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>
            <p><strong>ID Cliente:</strong> <?php echo $id_cliente ?? 'No vinculado'; ?></p>
            <p><strong>Producto:</strong> <?php echo $miPedido->producto; ?></p>
            <p><strong>Tipo:</strong> <?php echo $miPedido->tipoPedido; ?></p>
            <p><strong>Unidades:</strong> <?php echo $miPedido->unidades; ?></p>
            <p><strong>Observaciones:</strong> <?php echo $miPedido->observaciones; ?></p>
            <p><strong>Estado:</strong> <?php echo $miPedido->estado; ?></p>
            <p><strong>Fecha:</strong> <?php echo $miPedido->fecha; ?></p>
        </div>
        
        <br>
        <a href="formulario.php">Nuevo pedido</a> | 
        <a href="index.php">Regresar a la tienda</a> | 
        <a href="pago.php">Ir a pagar</a>
    </div>
</body>
</html>