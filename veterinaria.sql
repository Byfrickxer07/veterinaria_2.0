-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2025 a las 02:01:44
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de datos: `veterinaria`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `adopcion`
--

CREATE TABLE `adopcion` (
  `id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `tipo` enum('Perro','Gato') NOT NULL DEFAULT 'Perro',
  `raza` varchar(120) NOT NULL,
  `edad` varchar(60) DEFAULT NULL,
  `genero` enum('Macho','Hembra') DEFAULT 'Macho',
  `tamano` enum('Pequeño','Mediano','Grande') DEFAULT 'Mediano',
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `refugio` varchar(150) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(60) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `estado` enum('Disponible','Reservado','Adoptado') DEFAULT 'Disponible',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `adopcion`
--

INSERT INTO `adopcion` (`id`, `nombre`, `tipo`, `raza`, `edad`, `genero`, `tamano`, `descripcion`, `imagen`, `refugio`, `direccion`, `telefono`, `email`, `estado`, `creado_en`) VALUES
(1, 'Luna', 'Perro', 'Labrador', '2 años', 'Hembra', 'Grande', 'Luna es muy cariñosa y juguetona. Le encanta estar con niños.', 'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?w=800', 'Refugio Huellitas Felices', 'Av. San Martín 1234, Buenos Aires', '+54 11 1234-5678', 'contacto@huellitasfelices.com', 'Disponible', '2025-10-08 18:58:52'),
(2, 'Michi', 'Gato', 'Siamés', '1 año', 'Macho', 'Mediano', 'Gato tranquilo y cariñoso. Ideal para departamento.', 'https://images.unsplash.com/photo-1513360371669-4adf3dd7dff8?w=800', 'Fundación Animalitos', 'Calle Florida 567, CABA', '+54 11 2345-6789', 'info@animalitos.org', 'Disponible', '2025-10-08 18:58:52'),
(3, 'Rocky', 'Perro', 'Pastor Alemán', '3 años', 'Macho', 'Grande', 'Perro guardián, leal y protector. Necesita espacio para ejercitarse.', 'https://images.unsplash.com/photo-1568572933382-74d440642117?w=800', 'Refugio Huellitas Felices', 'Av. San Martín 1234, Buenos Aires', '+54 11 1234-5678', 'contacto@huellitasfelices.com', 'Reservado', '2025-10-08 18:58:52'),
(4, 'Nina', 'Gato', 'Mestizo', '8 meses', 'Hembra', 'Pequeño', 'Curiosa y juguetona. Se adapta rápido a nuevos hogares.', 'https://imgs.search.brave.com/togRKvyfxb3JguX5JmWiDjNn3lAYp4YL-bQObofJoHI/rs:fit:500:0:1:0/g:ce/aHR0cHM6Ly9nYXRv/cy5wbHVzL3dwLWNv/bnRlbnQvdXBsb2Fk/cy8yMDIxLzExL2dh/dG8tbWVzdGl6by1i/ZWJlLmpwZw', 'Patitas al Rescate', 'Av. Rivadavia 8900, CABA', '+54 11 4455-6677', 'adopciones@patitas.org', 'Disponible', '2025-10-08 18:58:52'),
(5, 'Toby', 'Perro', 'Beagle', '4 años', 'Macho', 'Mediano', '', 'https://imgs.search.brave.com/vaJT8RxrLYxIRvwZE0IzbZ2yQMpyKP4MQyAJdYODLJs/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9jZG4u/YnJpdGFubmljYS5j/b20vOTkvMTUyNDk5/LTA1MC0yOUVGQjdF/RS9CZWFnbGUuanBn/P3c9MzAw', 'Refugio Colitas Felices', 'Calle Sarmiento 456, La Plata', '+54 221 555-1212', 'colitas@refugio.org', 'Adoptado', '2025-10-08 18:58:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_clinico`
--

CREATE TABLE `historial_clinico` (
  `id` int(11) NOT NULL,
  `mascota_id` int(11) NOT NULL,
  `fecha_consulta` date NOT NULL,
  `motivo_consulta` text NOT NULL,
  `diagnostico` text DEFAULT NULL,
  `procedimientos_realizados` text DEFAULT NULL,
  `historial_vacunacion` text DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `medicamentos_actuales` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_clinico`
--

INSERT INTO `historial_clinico` (`id`, `mascota_id`, `fecha_consulta`, `motivo_consulta`, `diagnostico`, `procedimientos_realizados`, `historial_vacunacion`, `alergias`, `medicamentos_actuales`) VALUES
(7, 16, '2025-10-03', 'Control', 'Luego de unos meses sin venir al consultorio, vino a realizarse los chequeos regulares', 'revision auricular, revision ocular, revision de parasitos', 'Vacunas al dia', 'No posee ninguna alergia hasta el momento', 'Por ahora no toma nada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

CREATE TABLE `mascotas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `especie` varchar(50) NOT NULL,
  `raza` varchar(50) NOT NULL,
  `edad` int(11) NOT NULL,
  `sexo` varchar(10) NOT NULL,
  `peso` decimal(5,2) NOT NULL,
  `esterilizado` tinyint(1) NOT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mascotas`
--

INSERT INTO `mascotas` (`id`, `user_id`, `nombre`, `especie`, `raza`, `edad`, `sexo`, `peso`, `esterilizado`, `foto`) VALUES
(16, 17, 'Lolo', 'Perro', 'Golden Retriever', 3, 'Macho', 45.00, 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mascota_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `tipo_servicio` enum('cirugia','peluqueria','vacunacion','Control','castracion','baño') NOT NULL,
  `estado` enum('Pendiente','Terminado') DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id`, `user_id`, `mascota_id`, `fecha`, `hora`, `tipo_servicio`, `estado`) VALUES
(28, 17, 16, '2025-10-24', '08:00:00', 'Control', 'Pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `correo_electronico` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('cliente','admin','doctor') DEFAULT 'cliente',
  `telefono` varchar(20) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `apellido` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user`
--

INSERT INTO `user` (`id`, `nombre_usuario`, `correo_electronico`, `contrasena`, `rol`, `telefono`, `dni`, `apellido`) VALUES
(17, 'Cliente', 'cliente@gmail.com', '$2y$10$5VLy5vXInz6It7z0AI41y.ovOH8DXos.7lIDZAkBfQTL52GbZnKEW', 'cliente', '1121212122', '2222222', 'cliente'),
(18, 'Admin', 'admin@gmail.com', '$2y$10$9ynRWwLf2pZnl1Ht2bP33OmhlxbAwk/Ec5EujypD/QKHkLBUWIY6m', 'admin', '3123123213', '22313121', 'admin'),
(22, 'Doctor', 'doctor@gmail.com', '$2y$10$c6glyGEy3PY5gml6UmXFgOSvJL7rHNDXDc6EL9i1ozhchpeGMsIha', 'doctor', '1123344565', '22334455', 'doctor');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `adopcion`
--
ALTER TABLE `adopcion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_adopcion_nombre` (`nombre`),
  ADD KEY `idx_adopcion_tipo` (`tipo`),
  ADD KEY `idx_adopcion_estado` (`estado`);

--
-- Indices de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mascota_id` (`mascota_id`);

--
-- Indices de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `mascota_id` (`mascota_id`);

--
-- Indices de la tabla `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo_electronico` (`correo_electronico`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `adopcion`
--
ALTER TABLE `adopcion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD CONSTRAINT `historial_clinico_ibfk_1` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`);

--
-- Filtros para la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD CONSTRAINT `mascotas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `turnos_ibfk_2` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE;
COMMIT;
