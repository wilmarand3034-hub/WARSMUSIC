-- Ejecuta esto en tu base de datos warsmusic
-- Crea la tabla de pedidos para el carrito

CREATE TABLE IF NOT EXISTS pedidos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario     VARCHAR(100) NOT NULL,
    plan        VARCHAR(50)  NOT NULL,       -- 'free', 'mensual', 'anual'
    precio      DECIMAL(10,2) NOT NULL,
    fecha       DATETIME DEFAULT CURRENT_TIMESTAMP
);