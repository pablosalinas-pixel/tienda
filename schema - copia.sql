-- ==============

-- ==============

CREATE DATABASE IF NOT EXISTS tienda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tienda;

-- ------------------------------------------------------------
-- CLIENTE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CLIENTE (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    direccion VARCHAR(255),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- PRODUCTO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PRODUCTO (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(500),
    precio DECIMAL(12,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- COMPRA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS COMPRA (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    cantidad INT NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    id_producto INT NOT NULL,
    id_cliente INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- PEDIDO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PEDIDO (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(500),
    tipo VARCHAR(50),
    producto VARCHAR(150),
    unidades INT,
    observaciones VARCHAR(500),
    estado VARCHAR(30) DEFAULT 'Pendiente',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_cliente INT NULL,
    FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TRANSACCION_PAGO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS TRANSACCION_PAGO (
    id_transaccion INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    token_pasarela VARCHAR(255) NOT NULL,
    pasarela ENUM('webpay', 'stripe', 'paypal') NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    estado ENUM('iniciado', 'aprobado', 'rechazado', 'reversado') DEFAULT 'iniciado',
    respuesta_json TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_compra) REFERENCES COMPRA(id_compra),
    INDEX idx_token (token_pasarela),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- DATOS DE EJEMPLO
-- ------------------------------------------------------------

-- Cliente con id=1: coincide con el usuario_id que el login.php
-- asigna a la sesion del admin (usuario "admin" / clave "123456").
-- Sin esta fila, formulario.php y pago.php fallan al guardar el pedido/compra.
INSERT INTO CLIENTE (id_cliente, nombre, email, direccion) VALUES
(1, 'Administrador', 'admin@tienda.cl', 'Casa Matriz, Santiago');

INSERT INTO PRODUCTO (nombre, descripcion, precio, stock) VALUES
('Notebook HP G4', 'Notebook 14 pulgadas, i5, 8GB RAM, 256GB SSD', 550000, 9),
('Mouse Gamer con RGB', 'Mouse optico 7200dpi con luces RGB', 25000, 30),
('Teclado Mecánico Primus', 'Teclado mecanico switches rojos', 45000, 10),
('Monitor Sony 27"', 'Monitor Full HD 27 pulgadas', 190000, 8),
('Audífonos Bluetooth', 'Audifonos inalambricos con cancelacion de ruido', 45000, 10),
('Webcam Full 4K', 'Camara web 4K con microfono integrado', 40000, 8);
