<?php
require_once 'config_sesion.php';
require_once 'verificar_sesion.php';
require_once 'conexion.php';

$mensaje = "";
$tipo_mensaje = "";

// ==============
// Formulario
// ==============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'insertar_cliente') {
    
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    
    // Validaciones mediante PHP
    $errores = [];
    if (empty($nombre)) $errores[] = "El nombre es obligatorio";
    if (empty($email)) $errores[] = "El email es obligatorio";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "El formato de email no es válido";
    if (strlen($nombre) > 100) $errores[] = "El nombre no puede exceder 100 caracteres";
    
    if (empty($errores)) {
        try {
            $db = conectarDB();
            $stmt = $db->prepare("INSERT INTO CLIENTE (nombre, email, direccion) VALUES (:nombre, :email, :direccion)");
            $stmt->execute([
                ':nombre' => $nombre,
                ':email' => $email,
                ':direccion' => $direccion
            ]);
            $mensaje = "Cliente '$nombre' registrado exitosamente con ID: " . $db->lastInsertId();
            $tipo_mensaje = "success";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = "El email '$email' ya está registrado";
            } else {
                $mensaje = "Error al registrar: " . $e->getMessage();
            }
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = " " . implode(", ", $errores);
        $tipo_mensaje = "error";
    }
}

// ==============
// Listado de clientes
// ==============
$clientes = [];
try {
    $db = conectarDB();
    $stmt = $db->query("SELECT * FROM CLIENTE ORDER BY id_cliente DESC");
    $clientes = $stmt->fetchAll();
} catch (PDOException $e) {
    $mensaje = "Error al cargar clientes: " . $e->getMessage();
    $tipo_mensaje = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Clientes - Tienda</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { max-width: 900px; margin: 30px auto; }
        .form-box, .table-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
        .btn-submit { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background: #218838; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #28a745; color: white; }
        tr:hover { background: #f5f5f5; }
        .nav-links { margin-bottom: 20px; }
        .nav-links a { color: #0078d7; text-decoration: none; margin-right: 15px; }
        .nav-links a:hover { text-decoration: underline; }
        .error-input { border-color: #dc3545 !important; background: #fff5f5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="index.php">← Volver a la Tienda</a>
            <a href="gestionar_productos.php">← Gestionar Productos</a>
            <a href="gestionar_compras.php">Gestionar Compras →</a>
            <a href="gestionar_pedidos.php">Gestionar Pedidos →</a>
            <a href="reporte_clientes.php">Reporte Avanzado →</a>
        </div>
        
        <h1 style="text-align:center; color:#333; margin-bottom:20px;">Gestionar Clientes</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <!-- Insertar clientes Formulario -->
        <div class="form-box">
            <h2>Nuevo Cliente</h2>
            <form method="POST" action="" id="formCliente" onsubmit="return validarCliente()">
                <input type="hidden" name="accion" value="insertar_cliente">
                
                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100"
                           placeholder="Ej: Luna lunera ">
                </div>
                
                <div class="form-group">
                    <label for="email">Correo Electronico *</label>
                    <input type="email" id="email" name="email" required maxlength="100"
                           placeholder="Ej: Luna.lunera@yopmail.com">
                </div>
                
                <div class="form-group">
                    <label for="direccion">Direccion</label>
                    <input type="text" id="direccion" name="direccion" maxlength="255"
                           placeholder="Ej: Av. viña 123, Santiago">
                </div>
                
                <button type="submit" class="btn-submit">Guardar Cliente</button>
            </form>
        </div>
        
        <!-- TABLA DE CLIENTES -->
        <div class="table-box">
            <h2>Lista de Clientes</h2>
            <?php if (count($clientes) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Direccion</th>
                            <th>Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cli): ?>
                        <tr>
                            <td><?php echo $cli['id_cliente']; ?></td>
                            <td><?php echo htmlspecialchars($cli['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($cli['email']); ?></td>
                            <td><?php echo htmlspecialchars($cli['direccion'] ?? 'No especificada'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($cli['fecha_registro'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#666;">No hay clientes registrados.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Validacion -->
    <script>
    function validarCliente() {
        let nombre = document.getElementById('nombre').value.trim();
        let email = document.getElementById('email').value.trim();
        let errores = [];
        
        document.querySelectorAll('.error-input').forEach(el => el.classList.remove('error-input'));
        
        if (nombre.length < 3) {
            errores.push("El nombre debe tener al menos 3 caracteres");
            document.getElementById('nombre').classList.add('error-input');
        }
        
        // Validar email con regex
        let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            errores.push("Ingrese un email valido");
            document.getElementById('email').classList.add('error-input');
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