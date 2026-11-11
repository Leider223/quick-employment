-- =====================================================================
-- QUICK EMPLOYMENT - Script de base de datos
-- Base de datos única y compartida por los cuatro integrantes.
-- Motor: InnoDB (necesario para claves foráneas) | Codificación: utf8mb4
--
-- IMPORTANTE: al importar este archivo se BORRAN y se vuelven a crear
-- todas las tablas. Úsalo para empezar de cero, no cuando ya tengan
-- datos reales que quieran conservar.
-- =====================================================================

-- Le indica al servidor que este archivo está en UTF-8 (tildes y ñ)
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS quick_employment
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE quick_employment;

-- Se desactivan las claves foráneas un momento para poder borrar las
-- tablas en cualquier orden. Se vuelven a activar al final del script.
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS calificaciones;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS solicitudes;
DROP TABLE IF EXISTS disponibilidad;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS freelancer_habilidades;
DROP TABLE IF EXISTS habilidades;
DROP TABLE IF EXISTS perfiles_freelancer;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- MÓDULO 1: USUARIOS (Integrante 1)
-- =====================================================================

CREATE TABLE usuarios (
  id_usuario      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(100) NOT NULL,
  correo          VARCHAR(150) NOT NULL,
  contrasena      VARCHAR(255) NOT NULL,          -- hash de password_hash()
  rol             ENUM('cliente','freelancer') NOT NULL,
  fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE perfiles_freelancer (
  id_usuario    INT UNSIGNED NOT NULL,            -- PK y FK a la vez (1 a 1)
  presentacion  TEXT NULL,
  experiencia   TEXT NULL,
  disponible    TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_usuario),
  CONSTRAINT fk_perfiles_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE habilidades (
  id_habilidad  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(80) NOT NULL,
  PRIMARY KEY (id_habilidad),
  UNIQUE KEY uq_habilidades_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE freelancer_habilidades (
  id_freelancer  INT UNSIGNED NOT NULL,
  id_habilidad   INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_freelancer, id_habilidad),      -- no repite habilidades
  CONSTRAINT fk_fh_freelancer
    FOREIGN KEY (id_freelancer) REFERENCES usuarios (id_usuario)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_fh_habilidad
    FOREIGN KEY (id_habilidad) REFERENCES habilidades (id_habilidad)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- MÓDULO 2: SERVICIOS (Integrante 2)
-- =====================================================================

CREATE TABLE categorias (
  id_categoria  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(80) NOT NULL,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE servicios (
  id_servicio        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_freelancer      INT UNSIGNED NOT NULL,
  id_categoria       INT UNSIGNED NOT NULL,
  titulo             VARCHAR(150) NOT NULL,
  descripcion        TEXT NOT NULL,
  precio             DECIMAL(10,2) NOT NULL,
  estado             ENUM('activo','retirado') NOT NULL DEFAULT 'activo',
  fecha_publicacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_servicio),
  KEY idx_servicios_estado (estado),
  CONSTRAINT chk_servicios_precio CHECK (precio >= 0),
  CONSTRAINT fk_servicios_freelancer
    FOREIGN KEY (id_freelancer) REFERENCES usuarios (id_usuario)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_servicios_categoria
    FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE disponibilidad (
  id_disponibilidad  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_freelancer      INT UNSIGNED NOT NULL,
  dia                ENUM('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  hora_inicio        TIME NOT NULL,
  hora_fin           TIME NOT NULL,
  PRIMARY KEY (id_disponibilidad),
  CONSTRAINT chk_disponibilidad_horas CHECK (hora_fin > hora_inicio),
  CONSTRAINT fk_disponibilidad_freelancer
    FOREIGN KEY (id_freelancer) REFERENCES usuarios (id_usuario)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- MÓDULO 3: SOLICITUDES Y NOTIFICACIONES (Integrante 3)
-- =====================================================================

CREATE TABLE solicitudes (
  id_solicitud         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_cliente           INT UNSIGNED NOT NULL,
  id_servicio          INT UNSIGNED NOT NULL,
  descripcion          TEXT NOT NULL,
  fecha_solicitud      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_requerida      DATE NOT NULL,
  estado               ENUM('pendiente','aceptada','rechazada','cancelada','finalizada')
                       NOT NULL DEFAULT 'pendiente',
  confirma_cliente     TINYINT(1) NOT NULL DEFAULT 0,
  confirma_freelancer  TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_solicitud),
  KEY idx_solicitudes_estado (estado),
  CONSTRAINT fk_solicitudes_cliente
    FOREIGN KEY (id_cliente) REFERENCES usuarios (id_usuario)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_solicitudes_servicio
    FOREIGN KEY (id_servicio) REFERENCES servicios (id_servicio)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificaciones (
  id_notificacion  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario       INT UNSIGNED NOT NULL,         -- quien recibe el aviso
  id_solicitud     INT UNSIGNED NULL,
  mensaje          VARCHAR(255) NOT NULL,
  fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  leida            TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_notificacion),
  CONSTRAINT fk_notificaciones_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notificaciones_solicitud
    FOREIGN KEY (id_solicitud) REFERENCES solicitudes (id_solicitud)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- MÓDULO 4: PAGOS Y CALIFICACIONES (Integrante 4)
-- =====================================================================

CREATE TABLE pagos (
  id_pago       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_solicitud  INT UNSIGNED NOT NULL,
  valor         DECIMAL(10,2) NOT NULL,
  metodo        VARCHAR(50) NOT NULL,
  fecha_pago    DATE NOT NULL,
  PRIMARY KEY (id_pago),
  UNIQUE KEY uq_pagos_solicitud (id_solicitud),   -- un solo pago por solicitud
  CONSTRAINT chk_pagos_valor CHECK (valor > 0),
  CONSTRAINT fk_pagos_solicitud
    FOREIGN KEY (id_solicitud) REFERENCES solicitudes (id_solicitud)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE calificaciones (
  id_calificacion  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_solicitud     INT UNSIGNED NOT NULL,
  puntuacion       TINYINT UNSIGNED NOT NULL,
  comentario       TEXT NULL,
  fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_calificacion),
  UNIQUE KEY uq_calificaciones_solicitud (id_solicitud),  -- una por solicitud
  CONSTRAINT chk_calificaciones_puntuacion CHECK (puntuacion BETWEEN 1 AND 5),
  CONSTRAINT fk_calificaciones_solicitud
    FOREIGN KEY (id_solicitud) REFERENCES solicitudes (id_solicitud)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DATOS DE PRUEBA
-- Todos los usuarios tienen la contraseña:  Test1234
-- (el valor guardado es el hash de esa contraseña, no la contraseña)
-- =====================================================================

INSERT INTO usuarios (id_usuario, nombre, correo, contrasena, rol) VALUES
(1, 'Camila Torres',  'camila.cliente@test.com',  '$2y$10$aLR4UH4aJOVa7nuiyt8tR.gRc5BJpV1ghnJiC9q5Rlz3oe3onSoF2', 'cliente'),
(2, 'Andrés Pérez',   'andres.cliente@test.com',  '$2y$10$aLR4UH4aJOVa7nuiyt8tR.gRc5BJpV1ghnJiC9q5Rlz3oe3onSoF2', 'cliente'),
(3, 'Laura Gómez',    'laura.freelancer@test.com','$2y$10$aLR4UH4aJOVa7nuiyt8tR.gRc5BJpV1ghnJiC9q5Rlz3oe3onSoF2', 'freelancer'),
(4, 'Julián Ríos',    'julian.freelancer@test.com','$2y$10$aLR4UH4aJOVa7nuiyt8tR.gRc5BJpV1ghnJiC9q5Rlz3oe3onSoF2', 'freelancer'),
(5, 'Sofía Martínez', 'sofia.freelancer@test.com','$2y$10$aLR4UH4aJOVa7nuiyt8tR.gRc5BJpV1ghnJiC9q5Rlz3oe3onSoF2', 'freelancer');

INSERT INTO perfiles_freelancer (id_usuario, presentacion, experiencia, disponible) VALUES
(3, 'Diseñadora gráfica con enfoque en identidad de marca.', '4 años diseñando logos y piezas para redes sociales.', 1),
(4, 'Desarrollador web de sitios sencillos y rápidos.',      '3 años creando páginas con HTML, CSS y PHP.',        1),
(5, 'Redactora y traductora inglés-español.',                '5 años escribiendo contenidos y traduciendo textos.', 0);

INSERT INTO habilidades (id_habilidad, nombre) VALUES
(1, 'Diseño gráfico'),
(2, 'Photoshop'),
(3, 'HTML y CSS'),
(4, 'PHP'),
(5, 'Redacción'),
(6, 'Traducción');

INSERT INTO freelancer_habilidades (id_freelancer, id_habilidad) VALUES
(3, 1), (3, 2),
(4, 3), (4, 4),
(5, 5), (5, 6);

INSERT INTO categorias (id_categoria, nombre) VALUES
(1, 'Diseño'),
(2, 'Programación'),
(3, 'Redacción y traducción'),
(4, 'Otros');

INSERT INTO servicios (id_servicio, id_freelancer, id_categoria, titulo, descripcion, precio, estado) VALUES
(1, 3, 1, 'Diseño de logo',            'Logo profesional con dos propuestas y archivos finales.', 150000.00, 'activo'),
(2, 3, 1, 'Piezas para redes sociales','Paquete de 5 publicaciones con tu identidad de marca.',    90000.00,  'activo'),
(3, 4, 2, 'Página web de presentación','Sitio de una página con HTML, CSS y formulario en PHP.',   300000.00, 'activo'),
(4, 5, 3, 'Traducción de documentos',  'Traducción inglés-español de hasta 5 páginas.',            60000.00,  'activo'),
(5, 4, 2, 'Arreglo de errores en PHP', 'Revisión y corrección de errores en un proyecto pequeño.', 80000.00,  'retirado');

INSERT INTO disponibilidad (id_freelancer, dia, hora_inicio, hora_fin) VALUES
(3, 'lunes',     '08:00:00', '12:00:00'),
(3, 'miercoles', '14:00:00', '18:00:00'),
(4, 'martes',    '09:00:00', '13:00:00'),
(4, 'jueves',    '09:00:00', '13:00:00'),
(4, 'sabado',    '08:00:00', '12:00:00');

-- Una solicitud en cada estado para poder probar todos los flujos
INSERT INTO solicitudes
  (id_solicitud, id_cliente, id_servicio, descripcion, fecha_requerida, estado, confirma_cliente, confirma_freelancer) VALUES
(1, 1, 1, 'Necesito un logo para mi emprendimiento de postres.',      '2026-12-01', 'finalizada', 1, 1),
(2, 1, 3, 'Quiero una página para mostrar mis productos.',            '2026-12-15', 'aceptada',   1, 0),
(3, 2, 2, 'Publicaciones para el lanzamiento de mi tienda.',          '2026-11-30', 'pendiente',  0, 0),
(4, 2, 4, 'Traducir el contrato de un proveedor.',                    '2026-11-25', 'rechazada',  0, 0),
(5, 1, 2, 'Piezas para una campaña, pero ya no las necesito.',        '2026-12-05', 'cancelada',  0, 0);

INSERT INTO pagos (id_solicitud, valor, metodo, fecha_pago) VALUES
(1, 150000.00, 'Transferencia', '2026-11-05'),
(2, 300000.00, 'Efectivo',      '2026-11-08');

INSERT INTO calificaciones (id_solicitud, puntuacion, comentario) VALUES
(1, 5, 'Excelente trabajo, muy puntual y atenta a los cambios.');

INSERT INTO notificaciones (id_usuario, id_solicitud, mensaje, leida) VALUES
(3, 3, 'Tienes una nueva solicitud para tu servicio «Piezas para redes sociales».', 0),
(4, 2, 'El cliente registró el pago de la solicitud #2.',                          0),
(2, 4, 'Tu solicitud #4 fue rechazada.',                                          1),
(1, 1, 'La solicitud #1 fue finalizada. Ya puedes calificar el servicio.',        1);
