<?php
require_once 'config_sesion.php';
require_once 'comentarios.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Comercio Electronico</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .user-bar { background: #333; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; }
        .user-bar a { color: #4fc3f7; text-decoration: none; margin-left: 15px; }
        .user-bar a:hover { text-decoration: underline; }
        .cart-details { background: white; max-width: 600px; margin: 20px auto; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: none; }
        .cart-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .btn-pagar { display: inline-block; margin-top: 15px; padding: 12px 25px; background: #ff6b00; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .btn-pagar:hover { background: #e65c00; }
        .btn-ver-carrito { background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; margin-left: 10px; }
        .btn-ver-carrito:hover { background: #5a6268; }
        .btn-eliminar { background: #dc3545; color: white; border: none; padding: 3px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-mysql { background: #0078d7; color: white; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; margin-left: 10px; }
        .btn-mysql:hover { background: #005db1; }
    </style>
</head>
<body>

    <!-- Barra de usuario con sesion -->
    <div class="user-bar">
        <div>
             <?php echo isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Invitado'; ?>
        </div>
        <div>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="gestionar_productos.php" class="btn-mysql">Panel MySQL</a>
                <a href="formulario.php">Nuevo Pedido</a>
                <a href="pago.php"> Pagar</a>
                <a href="logout.php">Cerrar Sesion</a>
            <?php else: ?>
                <a href="login.php">Iniciar Sesion</a>
            <?php endif; ?>
        </div>
    </div>

    <header>
        <h1>Tienda de Comercio Electronico</h1>
        <p>Buscador dinamico de productos</p>
    </header>

    <div class="search-container">
        <input type="text" id="product-search" placeholder="Busqueda del producto..">
        <button id="search-btn">Buscar</button>
    </div>

    <div class="notification" id="promotion-message"></div>

    <div class="cart-container">
        <span id="estado-carrito">Productos en carrito: 0</span>
        <button class="btn-ver-carrito" onclick="toggleCarrito()">Ver Carrito</button>
    </div>

    <!-- Detalle carrito desde la sesion -->
    <div class="cart-details" id="cart-details">
        <h3>Tu Carrito</h3>
        <div id="cart-items"></div>
        <div style="margin-top: 15px; font-weight: bold; font-size: 18px;">
            Total: $<span id="cart-total">0</span>
        </div>
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <a href="pago.php" class="btn-pagar">Proceder al Pago Seguro →</a>
        <?php else: ?>
            <p style="color: #dc3545; margin-top: 10px;">Ingrese a la sesion para proceder con el pago</p>
        <?php endif; ?>
    </div>

    <div id="results-container"></div>

    <script src="script.js"></script>

    <!-- Heartbeat para mantener sesion activa -->
    <script>
        // Cada 10 minutos (600000 ms) actualiza el timestamp de sesion
        setInterval(function() {
            fetch('heartbeat.php');
        }, 600000);
    </script>

    <!-- Actualizar stock al cargar/volver a la pagina -->
    <script>
        // Forzar recarga de productos desde BD al mostrar la pagina
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && typeof cargarProductosDesdeBD === 'function') {
                cargarProductosDesdeBD();
                actualizarVistaCarrito();
            }
        });
    </script>

    <section style="max-width: 600px; margin: 30px auto; padding: 20px; background: white; border-radius: 10px;">
        <h2>Deja tu comentario</h2>
        
        <form method="POST" action="">
            <input type="hidden" name="accion" value="guardar">
            
            <label>Producto:</label>
            <select name="id_producto" required>
                <option value="1">Notebook HP G4</option>
                <option value="2">Mouse Gamer con RGB</option>
                <option value="3">Teclado Mecánico primus</option>
                <option value="4">Monitor Sony 27"</option>
                <option value="5">Audífonos Bluetooth</option>
                <option value="6">Webcam Full 4k</option>
            </select><br><br>
            
            <label>Su nombre:</label>
            <input type="text" name="nombre" required><br><br>
            
            <label>Calificacion:</label>
            <select name="calificacion" required>
                <option value="5">★★★★★ Excelente</option>
                <option value="4">★★★★☆ Muy bueno</option>
                <option value="3">★★★☆☆ Bueno</option>
                <option value="2">★★☆☆☆ Regular</option>
                <option value="1">★☆☆☆☆ Malo</option>
            </select><br><br>
            
            <label>Comentario:</label>
            <textarea name="comentario" required></textarea><br><br>
            
            <button type="submit">Enviar Comentario</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'guardar') {
            $resultado = guardarcomentarios($_POST['id_producto'], $_POST['nombre'], $_POST['calificacion'], $_POST['comentario']);
            echo "<p style='color: green;'><strong>$resultado</strong></p>";
        }
        
        if (file_exists('comentarios.txt')) {
            $listaComentarios = json_decode(file_get_contents('comentarios.txt'), true) ?: [];
            if (count($listaComentarios) > 0) {
                echo "<h3>Comentarios de clientes:</h3>";
                foreach ($listaComentarios as $r) {
                    $objComentario = new comentarios($r['idProducto'], $r['nombreUsuario'], $r['calificacion'], $r['comentario']);
                    echo "<div style='border-bottom: 1px solid #ccc; padding: 10px;'>";
                    echo "<strong>" . htmlspecialchars($r['nombreUsuario']) . "</strong> ";
                    echo "<span style='color: gold;'>" . $objComentario->mostrarEstrellas() . "</span><br>";
                    echo htmlspecialchars($r['comentario']) . "<br>";
                    echo "<small>" . $r['fecha'] . "</small>";
                    echo "</div>";
                }
            }
        }
        ?>
    </section>
</body>
</html>