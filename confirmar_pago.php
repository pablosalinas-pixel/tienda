<?php
/**
 * Ubicación: C:\xampp\htdocs\tienda\confirmar_pago.php
 * Página que recibe al usuario después de "pagar" en la pasarela simulada
 */

require_once 'verificar_sesion.php';
require_once 'conexion.php';

 $token = $_GET['token_ws'] ?? '';
 $mensaje = "";
 $estado_pago = "pendiente";

if (empty($token)) {
    $mensaje = "Error: No se recibió el token de la transacción.";
} else {
    try {
        $db = conectarDB();

        // Buscar la transacción en la base de datos usando el token
        $stmt = $db->prepare("SELECT id_transaccion, estado, monto FROM TRANSACCION_PAGO WHERE token_pasarela = :token");
        $stmt->execute([':token' => $token]);
        $transaccion = $stmt->fetch();

        if ($transaccion) {
            // Si la encontramos, actualizamos su estado a 'aprobado' (simulando que Webpay confirmó el pago)
            $stmt = $db->prepare("UPDATE TRANSACCION_PAGO SET estado = 'aprobado' WHERE token_pasarela = :token");
            $stmt->execute([':token' => $token]);

            $estado_pago = "aprobado";
            $mensaje = "¡Pago exitoso! Tu compra ha sido procesada y confirmada correctamente.";
        } else {
            $mensaje = "Error: No se encontró ninguna transacción con ese token en nuestro sistema.";
        }

    } catch (Exception $e) {
        $mensaje = "Error de base de datos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pago - Tienda</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); text-align: center; }
        .icono-exito { font-size: 60px; color: #28a745; margin-bottom: 20px; }
        .icono-error { font-size: 60px; color: #dc3545; margin-bottom: 20px; }
        .mensaje-exito { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin-bottom: 20px; font-size: 18px; }
        .mensaje-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; margin-bottom: 20px; font-size: 18px; }
        .btn-volver { display: inline-block; padding: 12px 24px; background: #0078d7; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
        .btn-volver:hover { background: #005fa3; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($estado_pago === "aprobado"): ?>
            <div class="icono-exito">✔️</div>
            <h1>¡Compra Confirmada!</h1>
            <div class="mensaje-exito">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
            <p>Gracias por tu preferencia. Pronto recibirás los detalles de tu pedido.</p>
        <?php else: ?>
            <div class="icono-error">❌</div>
            <h1>Hubo un problema</h1>
            <div class="mensaje-error">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
            <p>Si crees que esto es un error, por favor contacta a soporte.</p>
        <?php endif; ?>

        <a href="index.php" class="btn-volver">← Volver a la Tienda</a>
    </div>
</body>
</html>