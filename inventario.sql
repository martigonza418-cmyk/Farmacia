CREATE DATABASE farmacia_pos;
USE farmacia_pos;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol VARCHAR(50) DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
select * from usuarios;

CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_comercial VARCHAR(255) NOT NULL,
  presentacion VARCHAR(150),
  principio_activo VARCHAR(255),
  casa VARCHAR(150),
  expira DATE,
  stock INT DEFAULT 0,
  precio_costo DECIMAL(10,2) DEFAULT 0,
  precio_aprox DECIMAL(10,2) DEFAULT 0,
  precio_publico DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100),
  ip VARCHAR(45),
  attempts INT DEFAULT 0,
  last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT,
  total DECIMAL(10,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE venta_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venta_id INT,
  producto_id INT,
  cantidad INT,
  precio_unit DECIMAL(10,2),
  FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL
);



CREATE TABLE IF NOT EXISTS branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  address VARCHAR(512) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabla proveedores (suppliers)
CREATE TABLE IF NOT EXISTS suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  contact VARCHAR(255) DEFAULT NULL,
  nit VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Compras (purchase headers)
CREATE TABLE IF NOT EXISTS purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NULL,
  branch_id INT NULL,
  invoice_number VARCHAR(100) DEFAULT NULL,
  purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  total DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_by INT NULL, -- user id
  notes TEXT DEFAULT NULL,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Purchase items
CREATE TABLE IF NOT EXISTS purchase_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity DECIMAL(14,2) NOT NULL DEFAULT 0,
  unit_price DECIMAL(14,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE
  -- FK product_id asumido existente a tabla productos si la tienes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Apertura / cierre de caja (cash registers sessions)
CREATE TABLE IF NOT EXISTS cash_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NULL,
  user_id INT NOT NULL,
  opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  opening_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  closing_amount DECIMAL(14,2) NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  notes TEXT DEFAULT NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Pagos y formas de pago para ventas
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT DEFAULT NULL, -- referencia a la tabla ventas si existe
  purchase_id INT DEFAULT NULL, -- opcional para pagar compras
  branch_id INT DEFAULT NULL,
  user_id INT DEFAULT NULL,
  amount DECIMAL(14,2) NOT NULL,
  payment_type ENUM('efectivo','tarjeta','transferencia','otro') NOT NULL,
  payment_method_details VARCHAR(512) DEFAULT NULL, -- info tarjeta / trans ref
  is_credit BOOLEAN NOT NULL DEFAULT FALSE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Ventas: agregar campos necesarios (si ya tienes tabla ventas ajusta nombre)
ALTER TABLE ventas
  ADD COLUMN cliente_tipo ENUM('nit','consumidor_final','cui') DEFAULT 'consumidor_final',
  ADD COLUMN  cliente_documento VARCHAR(100) NULL,
  ADD COLUMN  payment_terms ENUM('contado','credito') DEFAULT 'contado',
  ADD COLUMN branch_id INT NULL,
  ADD COLUMN total_pending DECIMAL(14,2) DEFAULT 0,
  ADD COLUMN created_by INT NULL,
  ADD COLUMN notes TEXT DEFAULT NULL,
  ADD FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL;

INSERT INTO branches (name, address, created_at) VALUES
('Sucursal Central', 'Sede Central', NOW()),
('Sucursal 2', 'Sede Secundaria', NOW()),
('Sucursal 3', 'Sede Alterna', NOW());

select * from branches;


CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  request_ip VARCHAR(45) DEFAULT NULL,
  INDEX(user_id),
  INDEX(token_hash)
);

-- Tabla para metadatos de ventas (nit, cui, nota, sucursal)
CREATE TABLE IF NOT EXISTS venta_meta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venta_id INT,
  meta JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
