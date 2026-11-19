-- =====================================================================
-- QUICK EMPLOYMENT - Script de base de datos
-- Generado desde la base de datos real del equipo (phpMyAdmin 5.2.1,
-- MariaDB 10.4.32) e importable desde cero.
--
-- IMPORTANTE: al importar este archivo se BORRAN y se vuelven a crear
-- todas las tablas. Úsalo para empezar de cero, no cuando ya tengan
-- datos reales que quieran conservar.
--
-- Importar así:
--   phpMyAdmin -> pestaña Importar -> elegir este archivo -> utf-8
--   o por consola:  mysql -u root < database/quick_employment.sql
--
-- Contraseña de los usuarios de prueba:  Test1234
-- =====================================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `quick_employment`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `quick_employment`;

-- Se desactivan las claves foráneas un momento para poder borrar las
-- tablas en cualquier orden. Se vuelven a activar enseguida.
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `notificaciones`;
DROP TABLE IF EXISTS `calificaciones`;
DROP TABLE IF EXISTS `pagos`;
DROP TABLE IF EXISTS `solicitudes`;
DROP TABLE IF EXISTS `disponibilidad`;
DROP TABLE IF EXISTS `servicios`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `freelancer_habilidades`;
DROP TABLE IF EXISTS `habilidades`;
DROP TABLE IF EXISTS `perfiles_freelancer`;
DROP TABLE IF EXISTS `usuarios`;

SET FOREIGN_KEY_CHECKS = 1;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('cliente','freelancer') NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `contrasena`, `rol`, `fecha_registro`) VALUES
(1, 'Camila Torres', 'camila.cliente@test.com', '$2y$10$aLR4UH4aJOVa7nuiyt8tR.gRc5BJpV1ghnJiC9q5Rlz3oe3onSoF2', 'cliente', '2026-11-11 00:09:54'),
(2, 'Andrés Pérez', 'andres.cliente@test.com', '$2y$10$aLR4UH4aJOVa7nuiyt8tR.gRc5BJpV1ghnJiC9q5Rlz3oe3onSoF2', 'cliente', '2026-11-11 00:09:54'),
(3, 'Laura Gómez', 'laura.freelancer@test.com', '$2y$10$aLR4UH4aJOVa7nuiyt8tR.gRc5BJpV1ghnJiC9q5Rlz3oe3onSoF2', 'freelancer', '2026-11-11 00:09:54'),
(4, 'Julián Ríos', 'julian.freelancer@test.com', '$2y$10$aLR4UH4aJOVa7nuiyt8tR.gRc5BJpV1ghnJiC9q5Rlz3oe3onSoF2', 'freelancer', '2026-11-11 00:09:54'),
(5, 'Sofía Martínez', 'sofia.freelancer@test.com', '$2y$10$aLR4UH4aJOVa7nuiyt8tR.gRc5BJpV1ghnJiC9q5Rlz3oe3onSoF2', 'freelancer', '2026-11-11 00:09:54'),
(6, 'leider', 'prueba1@gmail.com', '$2y$10$RX7otkzd6BlSKT5S.7.9Vu5yGczaPJVskj9xg7ZEJ1zyKdTvwrDE6', 'cliente', '2026-11-11 14:57:23'),
(7, 'alonso posada', 'alonso.posada@test.com', '$2y$10$XrAFVPcwjnIfV90h5/XddOstftr5iCkHQvMU9bH8D97JJfpqshWwm', 'freelancer', '2026-11-12 16:17:42'),
(8, 'Camilo Posada', 'camilo.posada@gmail.com', '$2y$10$0lJ35aDoXOMKOEwyYpCJVuE9igmfjdVZguePiJbZl3xEzdCw8MPXK', 'cliente', '2026-11-13 17:43:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles_freelancer`
--

CREATE TABLE `perfiles_freelancer` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `presentacion` text DEFAULT NULL,
  `experiencia` text DEFAULT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `perfiles_freelancer`
--

INSERT INTO `perfiles_freelancer` (`id_usuario`, `presentacion`, `experiencia`, `disponible`) VALUES
(3, 'hola soy laura', '6 años diseñando', 1),
(4, 'Desarrollador web de sitios sencillos y rápidos.', '3 años creando páginas con HTML, CSS y PHP.', 1),
(5, 'Redactora y traductora inglés-español.', '5 años escribiendo contenidos y traduciendo textos.', 0),
(7, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habilidades`
--

CREATE TABLE `habilidades` (
  `id_habilidad` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habilidades`
--

INSERT INTO `habilidades` (`id_habilidad`, `nombre`) VALUES
(18, 'Atención al cliente'),
(14, 'Community manager'),
(17, 'Contabilidad'),
(15, 'Corrección de textos'),
(1, 'Diseño gráfico'),
(12, 'Diseño UX/UI'),
(8, 'Edición de video'),
(16, 'Excel'),
(9, 'Fotografía'),
(3, 'HTML y CSS'),
(7, 'Ilustración'),
(10, 'JavaScript'),
(13, 'Marketing digital'),
(11, 'MySQL'),
(2, 'Photoshop'),
(4, 'PHP'),
(5, 'Redacción'),
(6, 'Traducción');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `freelancer_habilidades`
--

CREATE TABLE `freelancer_habilidades` (
  `id_freelancer` int(10) UNSIGNED NOT NULL,
  `id_habilidad` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `freelancer_habilidades`
--

INSERT INTO `freelancer_habilidades` (`id_freelancer`, `id_habilidad`) VALUES
(3, 1),
(3, 2),
(4, 3),
(4, 4),
(5, 5),
(5, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`) VALUES
(1, 'Diseño'),
(4, 'Otros'),
(2, 'Programación'),
(3, 'Redacción y traducción');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id_servicio` int(10) UNSIGNED NOT NULL,
  `id_freelancer` int(10) UNSIGNED NOT NULL,
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `estado` enum('activo','retirado') NOT NULL DEFAULT 'activo',
  `fecha_publicacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id_servicio`, `id_freelancer`, `id_categoria`, `titulo`, `descripcion`, `precio`, `estado`, `fecha_publicacion`) VALUES
(1, 3, 1, 'Diseño de logo', 'Logo profesional con dos propuestas y archivos finales.', 150000.00, 'activo', '2026-11-11 00:09:54'),
(2, 3, 1, 'Piezas para redes sociales', 'Paquete de 5 publicaciones con tu identidad de marca.', 90000.00, 'activo', '2026-11-11 00:09:54'),
(3, 4, 2, 'Página web de presentación', 'Sitio de una página con HTML, CSS y formulario en PHP.', 300000.00, 'activo', '2026-11-11 00:09:54'),
(4, 5, 3, 'Traducción de documentos', 'Traducción inglés-español de hasta 5 páginas.', 60000.00, 'activo', '2026-11-11 00:09:54'),
(5, 4, 2, 'Arreglo de errores en PHP', 'Revisión y corrección de errores en un proyecto pequeño.', 80000.00, 'retirado', '2026-11-11 00:09:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidad`
--

CREATE TABLE `disponibilidad` (
  `id_disponibilidad` int(10) UNSIGNED NOT NULL,
  `id_freelancer` int(10) UNSIGNED NOT NULL,
  `dia` enum('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `disponibilidad`
--

INSERT INTO `disponibilidad` (`id_disponibilidad`, `id_freelancer`, `dia`, `hora_inicio`, `hora_fin`) VALUES
(1, 3, 'lunes', '08:00:00', '12:00:00'),
(2, 3, 'miercoles', '14:00:00', '18:00:00'),
(3, 4, 'martes', '09:00:00', '13:00:00'),
(4, 4, 'jueves', '09:00:00', '13:00:00'),
(5, 4, 'sabado', '08:00:00', '12:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id_solicitud` int(10) UNSIGNED NOT NULL,
  `id_cliente` int(10) UNSIGNED NOT NULL,
  `id_servicio` int(10) UNSIGNED NOT NULL,
  `descripcion` text NOT NULL,
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_requerida` date NOT NULL,
  `estado` enum('pendiente','aceptada','rechazada','cancelada','finalizada') NOT NULL DEFAULT 'pendiente',
  `confirma_cliente` tinyint(1) NOT NULL DEFAULT 0,
  `confirma_freelancer` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes`
--

INSERT INTO `solicitudes` (`id_solicitud`, `id_cliente`, `id_servicio`, `descripcion`, `fecha_solicitud`, `fecha_requerida`, `estado`, `confirma_cliente`, `confirma_freelancer`) VALUES
(1, 1, 1, 'Necesito un logo para mi emprendimiento de postres.', '2026-11-11 00:09:54', '2026-12-01', 'finalizada', 1, 1),
(2, 1, 3, 'Quiero una página para mostrar mis productos.', '2026-11-11 00:09:54', '2026-12-15', 'aceptada', 1, 0),
(3, 2, 2, 'Publicaciones para el lanzamiento de mi tienda.', '2026-11-11 00:09:54', '2026-11-30', 'pendiente', 0, 0),
(4, 2, 4, 'Traducir el contrato de un proveedor.', '2026-11-11 00:09:54', '2026-11-25', 'rechazada', 0, 0),
(5, 1, 2, 'Piezas para una campaña, pero ya no las necesito.', '2026-11-11 00:09:54', '2026-12-05', 'cancelada', 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(10) UNSIGNED NOT NULL,
  `id_solicitud` int(10) UNSIGNED NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `metodo` varchar(50) NOT NULL,
  `fecha_pago` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_solicitud`, `valor`, `metodo`, `fecha_pago`) VALUES
(1, 1, 150000.00, 'Transferencia', '2026-11-05'),
(2, 2, 300000.00, 'Efectivo', '2026-11-08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `id_calificacion` int(10) UNSIGNED NOT NULL,
  `id_solicitud` int(10) UNSIGNED NOT NULL,
  `puntuacion` tinyint(3) UNSIGNED NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `calificaciones`
--

INSERT INTO `calificaciones` (`id_calificacion`, `id_solicitud`, `puntuacion`, `comentario`, `fecha`) VALUES
(1, 1, 5, 'Excelente trabajo, muy puntual y atenta a los cambios.', '2026-11-11 00:09:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_solicitud` int(10) UNSIGNED DEFAULT NULL,
  `mensaje` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `leida` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `id_solicitud`, `mensaje`, `fecha`, `leida`) VALUES
(1, 3, 3, 'Tienes una nueva solicitud para tu servicio «Piezas para redes sociales».', '2026-11-11 00:09:54', 0),
(2, 4, 2, 'El cliente registró el pago de la solicitud #2.', '2026-11-11 00:09:54', 0),
(3, 2, 4, 'Tu solicitud #4 fue rechazada.', '2026-11-11 00:09:54', 1),
(4, 1, 1, 'La solicitud #1 fue finalizada. Ya puedes calificar el servicio.', '2026-11-11 00:09:54', 1);

-- =====================================================================
-- Índices y claves
-- =====================================================================

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uq_usuarios_correo` (`correo`);

ALTER TABLE `perfiles_freelancer`
  ADD PRIMARY KEY (`id_usuario`);

ALTER TABLE `habilidades`
  ADD PRIMARY KEY (`id_habilidad`),
  ADD UNIQUE KEY `uq_habilidades_nombre` (`nombre`);

ALTER TABLE `freelancer_habilidades`
  ADD PRIMARY KEY (`id_freelancer`,`id_habilidad`),
  ADD KEY `fk_fh_habilidad` (`id_habilidad`);

ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `uq_categorias_nombre` (`nombre`);

ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id_servicio`),
  ADD KEY `idx_servicios_estado` (`estado`),
  ADD KEY `fk_servicios_freelancer` (`id_freelancer`),
  ADD KEY `fk_servicios_categoria` (`id_categoria`);

ALTER TABLE `disponibilidad`
  ADD PRIMARY KEY (`id_disponibilidad`),
  ADD KEY `fk_disponibilidad_freelancer` (`id_freelancer`);

ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `idx_solicitudes_estado` (`estado`),
  ADD KEY `fk_solicitudes_cliente` (`id_cliente`),
  ADD KEY `fk_solicitudes_servicio` (`id_servicio`);

ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD UNIQUE KEY `uq_pagos_solicitud` (`id_solicitud`);

ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`id_calificacion`),
  ADD UNIQUE KEY `uq_calificaciones_solicitud` (`id_solicitud`);

ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `fk_notificaciones_usuario` (`id_usuario`),
  ADD KEY `fk_notificaciones_solicitud` (`id_solicitud`);

-- =====================================================================
-- AUTO_INCREMENT
-- =====================================================================

ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `perfiles_freelancer`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `habilidades`
  MODIFY `id_habilidad` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `servicios`
  MODIFY `id_servicio` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `disponibilidad`
  MODIFY `id_disponibilidad` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `solicitudes`
  MODIFY `id_solicitud` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `pagos`
  MODIFY `id_pago` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `calificaciones`
  MODIFY `id_calificacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- =====================================================================
-- Claves foráneas
-- =====================================================================

ALTER TABLE `perfiles_freelancer`
  ADD CONSTRAINT `fk_perfiles_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `freelancer_habilidades`
  ADD CONSTRAINT `fk_fh_freelancer` FOREIGN KEY (`id_freelancer`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fh_habilidad` FOREIGN KEY (`id_habilidad`) REFERENCES `habilidades` (`id_habilidad`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `servicios`
  ADD CONSTRAINT `fk_servicios_freelancer` FOREIGN KEY (`id_freelancer`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_servicios_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `disponibilidad`
  ADD CONSTRAINT `fk_disponibilidad_freelancer` FOREIGN KEY (`id_freelancer`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `solicitudes`
  ADD CONSTRAINT `fk_solicitudes_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solicitudes_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id_servicio`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes` (`id_solicitud`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `calificaciones`
  ADD CONSTRAINT `fk_calificaciones_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes` (`id_solicitud`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notificaciones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notificaciones_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes` (`id_solicitud`) ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================================
-- Reglas de validación (CHECK)
-- El dump de phpMyAdmin no las exporta, pero forman parte del diseño:
-- evitan precios negativos, horarios invertidos, pagos en cero y
-- calificaciones fuera de 1..5.
-- =====================================================================

ALTER TABLE `servicios`
  ADD CONSTRAINT `chk_servicios_precio` CHECK (`precio` >= 0);

ALTER TABLE `disponibilidad`
  ADD CONSTRAINT `chk_disponibilidad_horas` CHECK (`hora_fin` > `hora_inicio`);

ALTER TABLE `pagos`
  ADD CONSTRAINT `chk_pagos_valor` CHECK (`valor` > 0);

ALTER TABLE `calificaciones`
  ADD CONSTRAINT `chk_calificaciones_puntuacion` CHECK (`puntuacion` BETWEEN 1 AND 5);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
