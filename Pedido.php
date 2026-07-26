<?php
class Pedido {
    public $descripcionPedido;
    public $tipoPedido;
    public $producto;
    public $unidades;
    public $observaciones;
    public $estado;
    public $fecha;
    public $id_cliente; // ← NUEVO: Relación con tabla CLIENTE
    
    public function __construct($descripcion, $tipo, $producto, $unidades, $observaciones, $id_cliente = null) {
        $this->descripcionPedido = $descripcion;
        $this->tipoPedido = $tipo;
        $this->producto = $producto;
        $this->unidades = $unidades;
        $this->observaciones = $observaciones;
        $this->estado = "Pendiente";
        $this->fecha = date("Y-m-d H:i:s");
        $this->id_cliente = $id_cliente; // ← NUEVO
    }
    
    // NUEVO: Guardar en base de datos MySQL
    public function guardarEnDB($db) {
        try {
            // Validar que el cliente existe
            if ($this->id_cliente !== null) {
                $check = $db->prepare("SELECT id_cliente FROM CLIENTE WHERE id_cliente = :id");
                $check->execute([':id' => $this->id_cliente]);
                if (!$check->fetch()) {
                    throw new Exception("Cliente ID {$this->id_cliente} no encontrado");
                }
            }
            
            $stmt = $db->prepare("
                INSERT INTO PEDIDO (descripcion, tipo, producto, unidades, observaciones, estado, fecha, id_cliente) 
                VALUES (:desc, :tipo, :prod, :uni, :obs, :estado, :fecha, :id_cli)
            ");
            
            return $stmt->execute([
                ':desc' => $this->descripcionPedido,
                ':tipo' => $this->tipoPedido,
                ':prod' => $this->producto,
                ':uni' => $this->unidades,
                ':obs' => $this->observaciones,
                ':estado' => $this->estado,
                ':fecha' => $this->fecha,
                ':id_cli' => $this->id_cliente
            ]);
            
        } catch (PDOException $e) {
            error_log("[Pedido::guardarEnDB] Error PDO: " . $e->getMessage());
            return false;
        }
    }
    
    // NUEVO: Actualizar estado con validación
    public static function actualizarEstado($db, $id_pedido, $nuevo_estado) {
        $estados_permitidos = ['Pendiente', 'Procesando', 'Enviado', 'Entregado', 'Cancelado'];
        if (!in_array($nuevo_estado, $estados_permitidos)) {
            return false;
        }
        
        $stmt = $db->prepare("UPDATE PEDIDO SET estado = :estado WHERE id_pedido = :id");
        return $stmt->execute([':estado' => $nuevo_estado, ':id' => $id_pedido]);
    }
}
?>