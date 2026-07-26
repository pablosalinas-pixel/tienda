<?php
require_once 'config_sesion.php';
require_once 'verificar_sesion.php';
require_once 'conexion.php';
require_once 'Pedido.php';

$mensaje = "";
$tipo_mensaje = "";

// ==============
// Cambiar estado de un pedido (usa Pedido::actualizarEstado, ya existia en Pedido.php)
// ==============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado') {

    $id_pedido = intval($_POST['id_pedido'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    try {
        $db = conectarDB();
        $ok = Pedido::actualizarEstado($db, $id_pedido, $nuevo_estado);

        if ($ok) {
            $mensaje = "Estado del pedido #$id_pedido actualizado a '$nuevo_estado'";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "No se pudo actualizar el pedido (estado invalido o pedido inexistente)";
            $tipo_mensaje = "error";
        }
    } catch (PDOException $e) {
        $mensaje = "Error de base de datos: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// ==============
// Listado de pedidos con datos del cliente
// ==============
$pedidos = [];
try {
    $db = conectarDB();
    $stmt = $db->query("
        SELECT p.*, c.nombre AS nombre_cliente
        FROM PEDIDO p
        LEFT JOIN CLIENTE c ON p.id_cliente = c.id_cliente
        ORDER BY p.fecha DESC
    ");
    $pedidos = $stmt->fetchAll();
} catch (PDOException $e) {
    $mensaje = "Error al cargar pedidos: " . $e->getMessage();
    $tipo_mensaje = "error";
}

$estados_permitidos = ['Pendiente', 'Procesando', 'Enviado', 'Entregado', 'Cancelado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Pedidos - Tienda</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { max-width: 1000px; margin: 30px auto; }
        .table-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); margin-bottom: 30px; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #6f42c1; color: white; }
        tr:hover { background: #f5f5f5; }
        .nav-links { margin-bottom: 20px; }
        .nav-links a { color: #0078d7; text-decoration: none; margin-right: 15px; }
        .nav-links a:hover { text-decoration: underline; }
        .estado-form { display: flex; gap: 8px; align-items: center; }
        .estado-form select { padding: 6px; border-radius: 4px; border: 1px solid #ccc; }
        .estado-form button { padding: 6px 14px; background: #6f42c1; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .estado-form button:hover { background: #59339e; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; }
        .badge-Pendiente { background: #6c757d; }
        .badge-Procesando { background: #0078d7; }
        .badge-Enviado { background: #ff6b00; }
        .badge-Entregado { background: #28a745; }
        .badge-Cancelado { background: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="index.php">← Volver a la Tienda</a>
            <a href="gestionar_productos.php">Gestionar Productos →</a>
            <a href="gestionar_clientes.php">Gestionar Clientes →</a>
            <a href="gestionar_compras.php">Gestionar Compras →</a>
            <a href="reporte_clientes.php">Reporte Avanzado →</a>
        </div>

        <h1 style="text-align:center; color:#333; margin-bottom:20px;">Gestionar Pedidos</h1>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="table-box">
            <h2>Listado de Pedidos</h2>
            <?php if (count($pedidos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Unidades</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Cambiar Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $ped): ?>
                        <tr>
                            <td>#<?php echo $ped['id_pedido']; ?></td>
                            <td><?php echo htmlspecialchars($ped['nombre_cliente'] ?? 'Sin vincular'); ?></td>
                            <td><?php echo htmlspecialchars($ped['producto']); ?></td>
                            <td><?php echo htmlspecialchars($ped['tipo']); ?></td>
                            <td><?php echo $ped['unidades']; ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($ped['estado']); ?>"><?php echo htmlspecialchars($ped['estado']); ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($ped['fecha'])); ?></td>
                            <td>
                                <form method="POST" action="" class="estado-form">
                                    <input type="hidden" name="accion" value="cambiar_estado">
                                    <input type="hidden" name="id_pedido" value="<?php echo $ped['id_pedido']; ?>">
                                    <select name="nuevo_estado">
                                        <?php foreach ($estados_permitidos as $est): ?>
                                            <option value="<?php echo $est; ?>" <?php echo $est === $ped['estado'] ? 'selected' : ''; ?>><?php echo $est; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Actualizar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#666;">No hay pedidos registrados todavia. Puedes crear uno desde <a href="formulario.php">Nuevo Pedido</a>.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
