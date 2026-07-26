<?php
require_once 'config_sesion.php';
require_once 'verificar_sesion.php';
require_once 'conexion.php';

// ==============
// Clientes con mas de 2 compras
// ==============
$clientes_frecuentes = [];
$total_compras = 0;
$total_ingresos = 0;

try {
    $db = conectarDB();
    
    // Consulta mediante join , by e heving 
    $stmt = $db->query("
        SELECT 
            cl.id_cliente,
            cl.nombre AS nombre_cliente,
            cl.email,
            COUNT(c.id_compra) AS numero_compras,
            SUM(c.total) AS monto_total,
            AVG(c.total) AS promedio_compra,
            MAX(c.fecha) AS ultima_compra,
            GROUP_CONCAT(DISTINCT p.nombre SEPARATOR ', ') AS productos_comprados
        FROM CLIENTE cl
        INNER JOIN COMPRA c ON cl.id_cliente = c.id_cliente
        INNER JOIN PRODUCTO p ON c.id_producto = p.id_producto
        GROUP BY cl.id_cliente, cl.nombre, cl.email
        HAVING COUNT(c.id_compra) > 2
        ORDER BY numero_compras DESC, monto_total DESC
    ");
    $clientes_frecuentes = $stmt->fetchAll();
    
    // Estadisticas
    $stmt = $db->query("SELECT COUNT(*) as total FROM COMPRA");
    $total_compras = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT SUM(total) as total FROM COMPRA");
    $total_ingresos = $stmt->fetch()['total'] ?? 0;
    
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Avanzado - Clientes Frecuentes</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { max-width: 1100px; margin: 30px auto; }
        .report-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #0078d7, #005db1); color: white; padding: 25px; border-radius: 10px; text-align: center; }
        .stat-card h3 { font-size: 36px; margin: 10px 0; }
        .stat-card p { font-size: 14px; opacity: 0.9; }
        .stat-card.orange { background: linear-gradient(135deg, #ff6b00, #e65c00); }
        .stat-card.green { background: linear-gradient(135deg, #28a745, #218838); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #6f42c1; color: white; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-gold { background: #ffd700; color: #333; }
        .badge-silver { background: #c0c0c0; color: #333; }
        .badge-bronze { background: #cd7f32; color: white; }
        .monto { font-weight: bold; color: #28a745; font-size: 16px; }
        .nav-links { margin-bottom: 20px; }
        .nav-links a { color: #0078d7; text-decoration: none; margin-right: 15px; }
        .nav-links a:hover { text-decoration: underline; }
        .query-box { background: #f8f9fa; border-left: 4px solid #6f42c1; padding: 20px; margin: 20px 0; font-family: 'Courier New', monospace; font-size: 13px; overflow-x: auto; }
        .query-box h4 { color: #6f42c1; margin-bottom: 10px; }
        .no-results { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="index.php">← Volver a la Tienda</a>
            <a href="gestionar_productos.php">← Productos</a>
            <a href="gestionar_clientes.php">← Clientes</a>
            <a href="gestionar_compras.php">← Compras</a>
            <a href="gestionar_pedidos.php">Gestionar Pedidos →</a>
        </div>
        
        <h1 style="text-align:center; color:#333; margin-bottom:20px;">Reporte Avanzado: Clientes Frecuentes</h1>
        
        <!-- Estadisticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <p>Total Compras</p>
                <h3><?php echo $total_compras; ?></h3>
            </div>
            <div class="stat-card orange">
                <p>Ingresos Totales</p>
                <h3>$<?php echo number_format($total_ingresos, 0, ',', '.'); ?></h3>
            </div>
            <div class="stat-card green">
                <p>Clientes Frecuentes (>2)</p>
                <h3><?php echo count($clientes_frecuentes); ?></h3>
            </div>
        </div>
        
        <!--Consultas de sql-->
        <div class="report-box">
            <h2>Consulta SQL Utilizada</h2>
            <div class="query-box">
                <h4>Consulta Avanzada con JOIN, GROUP BY E HAVING:</h4>
<pre>SELECT 
    cl.id_cliente,
    cl.nombre AS nombre_cliente,
    cl.email,
    COUNT(c.id_compra) AS numero_compras,
    SUM(c.total) AS monto_total,
    AVG(c.total) AS promedio_compra,
    MAX(c.fecha) AS ultima_compra,
    GROUP_CONCAT(DISTINCT p.nombre SEPARATOR ', ') AS productos_comprados
FROM CLIENTE cl
INNER JOIN COMPRA c ON cl.id_cliente = c.id_cliente
INNER JOIN PRODUCTO p ON c.id_producto = p.id_producto
GROUP BY cl.id_cliente, cl.nombre, cl.email
HAVING COUNT(c.id_compra) > 2
ORDER BY numero_compras DESC, monto_total DESC</pre>
            </div>
        </div>
        
        <!-- RESULTADOS -->
        <div class="report-box">
            <h2>Clientes con Mas de 2 Compras</h2>
            
            <?php if (count($clientes_frecuentes) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ranking</th>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>N° Compras</th>
                            <th>Monto Total</th>
                            <th>Promedio</th>
                            <th>Ultima Compra</th>
                            <th>Productos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($clientes_frecuentes as $cli): 
                            $badge_class = $rank == 1 ? 'badge-gold' : ($rank == 2 ? 'badge-silver' : ($rank == 3 ? 'badge-bronze' : ''));
                        ?>
                        <tr>
                            <td>
                                <span class="badge <?php echo $badge_class; ?>">
                                    #<?php echo $rank++; ?>
                                </span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($cli['nombre_cliente']); ?></strong></td>
                            <td><?php echo htmlspecialchars($cli['email']); ?></td>
                            <td style="font-size:18px; font-weight:bold;"><?php echo $cli['numero_compras']; ?></td>
                            <td class="monto">$<?php echo number_format($cli['monto_total'], 0, ',', '.'); ?></td>
                            <td>$<?php echo number_format($cli['promedio_compra'], 0, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cli['ultima_compra'])); ?></td>
                            <td><small><?php echo htmlspecialchars($cli['productos_comprados']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <h3>clientes con mas 2 compras no encontrados</h3>
                    <p>Registre mas compras para ver resultados en este reporte.</p>
                    <a href="gestionar_compras.php" style="color:#0078d7;">Ir a registrar compras →</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Detalles de consultas -->
        <div class="report-box">
            <h2>Explicación de la Consulta Avanzada</h2>
            <ul style="line-height: 2; color: #555;">
                <li><strong>INNER JOIN:</strong> Une las tablas CLIENTE, COMPRA y PRODUCTO mediante sus claves foráneas</li>
                <li><strong>COUNT(c.id_compra):</strong> Cuenta el número de compras realizadas por cada cliente</li>
                <li><strong>SUM(c.total):</strong> Calcula el monto total gastado por cada cliente</li>
                <li><strong>AVG(c.total):</strong> Obtiene el promedio de gasto por compra</li>
                <li><strong>MAX(c.fecha):</strong> Muestra la fecha de la última compra</li>
                <li><strong>GROUP_CONCAT:</strong> Lista los productos comprados por cada cliente</li>
                <li><strong>GROUP BY:</strong> Agrupa los resultados por cliente</li>
                <li><strong>HAVING COUNT(c.id_compra) > 2:</strong> Filtra solo clientes con más de 2 compras</li>
                <li><strong>ORDER BY:</strong> Ordena por número de compras y monto total descendente</li>
            </ul>
        </div>
    </div>
</body>
</html>