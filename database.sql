CREATE TABLE IF NOT EXISTS usuarios (
 id INT AUTO_INCREMENT PRIMARY KEY,
 pc_identificador VARCHAR(100) UNIQUE NOT NULL,
 nombre_usuario VARCHAR(100) NOT NULL,
 rol ENUM('superadmin','usuario') DEFAULT 'usuario',
 activo BOOLEAN DEFAULT TRUE,
 fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
 fecha_ultima_conexion DATETIME NULL,
 estado_pc ENUM('buena','lenta','fallando') DEFAULT 'buena'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS tickets (
 id INT AUTO_INCREMENT PRIMARY KEY,
 titulo VARCHAR(255) NOT NULL,
 descripcion TEXT NOT NULL,
 foto VARCHAR(255) NULL,
 pc_origen VARCHAR(100) NOT NULL,
 usuario_origen VARCHAR(100) NOT NULL,
 fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
 estado ENUM('pendiente','aprobado','rechazado','resuelto') DEFAULT 'pendiente',
 aprobado_por VARCHAR(100) NULL, fecha_aprobacion DATETIME NULL,
 rechazado_por VARCHAR(100) NULL, fecha_rechazo DATETIME NULL,
 motivo_rechazo TEXT NULL,
 resuelto_por VARCHAR(100) NULL, fecha_resolucion DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS configuracion (clave VARCHAR(100) PRIMARY KEY, valor TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO configuracion VALUES ('version_sistema','1.0'),('tickets_por_pagina','10');
