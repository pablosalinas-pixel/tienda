<?php
/**
 * 
 * Ubicación: C:\xampp\htdocs\tienda\pago.php
 */

require_once 'verificar_sesion.php';
require_once 'conexion.php';
require_once 'PasarelaPago.php'; 

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

 $mensaje = "";
 $carrito_vacio = true;
 $total = 0;

// Validar si existen productos en el carrito
if (!empty($_SESSION['carrito'])) {
    $carrito_vacio = false;
    foreach ($_SESSION['carrito'] as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
}

// ==============
// PROCESAR PAGO CON PASARELA
// ==============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = "Error: Token de seguridad invalido.";
    } elseif ($carrito_vacio) {
        $mensaje = "Error: Tu carrito esta vacio.";
    } else {

        try {
            $db = conectarDB();
            $db->beginTransaction();

            // 1) Verificar stock disponible para todos los productos
            foreach ($_SESSION['carrito'] as $item) {
                $stmt = $db->prepare("SELECT stock, nombre FROM PRODUCTO WHERE id_producto = :id");
                $stmt->execute([':id' => $item['id']]);
                $producto = $stmt->fetch();

                if (!$producto) {
                    throw new Exception("Producto no encontrado en BD");
                }

                if ($producto['stock'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para: " . $producto['nombre']);
                }
            }

            // ==============
            // Iniciar pago con pasarela configurada
            // ==============
            $tipo_pasarela = strtolower($_ENV['PASARELA_PAGO_DEFAULT'] ?? 'webpay');
            $pasarela = PasarelaPagoFactory::crear($tipo_pasarela); 

            $orden_id = 'ORD_' . time() . '_' . $_SESSION['usuario_id'];
            $resultado_pago = $pasarela->iniciarPago([
                'orden' => $orden_id,
                'monto' => $total,
                'session_id' => session_id(),
                'url_retorno' => 'https://tutienda.com/confirmar_pago.php',
                'descripcion' => 'Compra en Tienda de Comercio Electrónico'
            ]);

            if (isset($resultado_pago['error'])) {
                throw new Exception("Error pasarela: " . $resultado_pago['error']);
            }

            // ==============
            // Actualizar stock, registrar compra y transaccion de pago
            // ==============
            foreach ($_SESSION['carrito'] as $item) {
                // Actualizar stock
                $stmt = $db->prepare("UPDATE PRODUCTO SET stock = stock - :cantidad WHERE id_producto = :id");
                $stmt->execute([
                    ':cantidad' => $item['cantidad'],
                    ':id' => $item['id']
                ]);

                // Registrar compra
                $stmt = $db->prepare("
                    INSERT INTO COMPRA (cantidad, total, id_producto, id_cliente) 
                    VALUES (:cantidad, :total, :id_producto, :id_cliente)
                ");
                $item_total = $item['precio'] * $item['cantidad'];
                $stmt->execute([
                    ':cantidad' => $item['cantidad'],
                    ':total' => $item_total,
                    ':id_producto' => $item['id'],
                    ':id_cliente' => $_SESSION['usuario_id'] ?? 1
                ]);
                $id_compra = $db->lastInsertId();

                // Guardar transacción en BD
                $stmt = $db->prepare("
                    INSERT INTO TRANSACCION_PAGO 
                    (id_compra, token_pasarela, pasarela, monto, estado) 
                    VALUES (:id_compra, :token, :pasarela, :monto, 'iniciado')
                ");
                $stmt->execute([
                    ':id_compra' => $id_compra,
                    ':token' => $resultado_pago['token'],
                    ':pasarela' => $tipo_pasarela,
                    ':monto' => $item_total
                ]);
            }

            $db->commit();

            //Vaciar carrito
            $_SESSION['carrito'] = [];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['pago_token'] = $resultado_pago['token'];

            // ==========================================
            // SOLUCIÓN A LA PÁGINA EN BLANCO DEFINITIVA
            // ==========================================
            // NO podemos usar header("Location: ...") para ir a Transbank con un token de mentira.
            // Transbank exige un formulario POST y tokens reales.
            // Para probar localmente, simulamos que el pago fue exitoso y redirigimos 
            // directamente a la página de confirmación de nuestra propia tienda.
            $url_confirmacion_local = 'confirmar_pago.php?token_ws=' . $resultado_pago['token'];
            header("Location: " . $url_confirmacion_local);
            exit();

        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $mensaje = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar - Tienda</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .cart-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .total-final { display: flex; justify-content: space-between; font-size: 20px; font-weight: bold; margin-top: 15px; padding-top: 15px; border-top: 2px solid #333; }
        .btn-pagar { width: 100%; padding: 14px; background: #ff6b00; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        .btn-pagar:hover { background: #e65c00; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .nav-links { margin-bottom: 20px; }
        .nav-links a { color: #0078d7; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="index.php">← Volver a la Tienda</a>
        </div>

        <h1>Confirmar Pago</h1>

        <?php if ($mensaje): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <?php if ($carrito_vacio): ?>
            <p style="color:#666;">Tu carrito esta vacio. <a href="index.php">Vuelve a la tienda</a> para agregar productos.</p>
        <?php else: ?>
            <div id="resumen-carrito">
                <?php foreach ($_SESSION['carrito'] as $item): ?>
                    <div class="cart-item">
                        <span><?php echo htmlspecialchars($item['nombre']); ?> x<?php echo $item['cantidad']; ?></span>
                        <span>$<?php echo number_format($item['precio'] * $item['cantidad'], 0, ',', '.'); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="total-final">
                    <span>Total</span>
                    <span>$<?php echo number_format($total, 0, ',', '.'); ?></span>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" class="btn-pagar">Pagar $<?php echo number_format($total, 0, ',', '.'); ?></button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>