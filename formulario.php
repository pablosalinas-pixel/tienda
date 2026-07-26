<?php require_once 'verificar_sesion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Pedido</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div style="max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
        <h1>Registrar Nuevo Pedido</h1>
        <p style="color: #0078d7;">Sesion activa: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>
        
        <form action="procesar.php" method="POST">
            <label>Descripcion del pedido:</label>
            <textarea name="descripcion" required></textarea><br><br>
            
            <label>Tipo de pedido:</label>
            <select name="tipo" required>
                <option value="Normal">Normal</option>
                <option value="Express">Express</option>
                <option value="Internacional">Internacional</option>
            </select><br><br>
            
            <label>Producto:</label>
            <input type="text" name="producto" required><br><br>
            
            <label>Unidades:</label>
            <input type="number" name="unidades" min="1" required><br><br>
            
            <label>Observaciones:</label>
            <textarea name="observaciones"></textarea><br><br>
            
            <button type="submit">Registrar Pedido</button>
        </form>
        <p style="margin-top: 20px;"><a href="index.php">← Regresar a la tienda</a></p>
    </div>
</body>
</html>