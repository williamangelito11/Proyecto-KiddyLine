-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-10-2026 a las 07:23:16
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `kiddylineropabebe`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulos`
--

CREATE TABLE `articulos` (
  `id_articulo` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `codigo_referencia` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo_articulo` enum('materia_prima','en_proceso','producto_terminado') NOT NULL,
  `talla` varchar(20) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `stock_actual` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `precio_costo` decimal(10,2) DEFAULT 0.00,
  `precio_venta` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `articulos`
--

INSERT INTO `articulos` (`id_articulo`, `id_categoria`, `codigo_referencia`, `nombre`, `tipo_articulo`, `talla`, `color`, `stock_actual`, `stock_minimo`, `precio_costo`, `precio_venta`) VALUES
(2, 1, '10', 'Body Manga Larga', 'producto_terminado', '2', 'Blanco', 7, 5, 12000.00, 18000.00),
(7, 1, '09', 'Body Manga Corta', 'producto_terminado', '6-9 meses', 'Blanco', 8, 5, 12000.00, 18000.00),
(8, 1, '08', 'Mameluco Manga Corta', 'producto_terminado', '6-9 meses', 'Blanco', 4, 5, 20000.00, 30000.00),
(9, 1, '07', 'Pijama niño', 'producto_terminado', '3-6 meses', 'Azul-verde-Blanco', 2, 7, 35000.00, 44900.00),
(11, 1, '11', 'Gorrito Antialérgico x3', 'producto_terminado', 'Única', 'Celeste', 24, 5, 8000.00, 12000.00),
(12, 5, '12', 'Gorrito Antialérgico', 'producto_terminado', 'Única', 'Celeste', 14, 5, 3000.00, 6500.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `tipo` enum('materia_prima','producto_terminado') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`, `tipo`) VALUES
(1, 'Ropa de Bebé', 'producto_terminado'),
(4, 'Ropa de Invierno', ''),
(5, 'Ropa recien nacido', 'producto_terminado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `documento`, `nombre`, `telefono`, `email`, `direccion`) VALUES
(1, '1010123456', 'Distribuidora Bebé Feliz', '3105551122', 'contacto@bebefeliz.com', 'Calle 10 # 5-20'),
(2, '1020987654', 'Confecciones Little Star', '3204443311', 'compras@littlestar.com', 'Carrera 15 # 8-45'),
(3, '1030555666', 'Bebés & Moda Infantil', '3157778899', 'ventas@bebesymoda.com', 'Av. Central # 12-30'),
(6, '10103456789', 'Sofia Lopez (Actualizado)', '3133456789', 'sofialopez@gmail.com', 'Transversal 52 # 14-20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobantes`
--

CREATE TABLE `comprobantes` (
  `id_comprobante` int(11) NOT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `id_orden_compra` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comprobantes`
--

INSERT INTO `comprobantes` (`id_comprobante`, `tipo`, `concepto`, `monto`, `fecha`, `id_venta`, `id_orden_compra`) VALUES
(4, 'ingreso', 'Pago inicial por pedido de mamelucos de bebé', 150000.00, '2026-10-04', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contratos_documentos`
--

CREATE TABLE `contratos_documentos` (
  `id_contrato` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `tipo_contrato` varchar(50) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `salario` decimal(10,2) NOT NULL,
  `ruta_archivo_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `contratos_documentos`
--

INSERT INTO `contratos_documentos` (`id_contrato`, `id_empleado`, `tipo_contrato`, `fecha_inicio`, `fecha_fin`, `salario`, `ruta_archivo_pdf`) VALUES
(3, 3, 'Termino Indefinido', '2026-10-05', NULL, 0.00, 'documento.02.pdf'),
(4, 9, 'Termino Indefinido', '2026-10-05', NULL, 2200000.00, 'documento.03.pdf'),
(9, 2, 'Termino Indefinido', '2026-10-04', NULL, 1300000.00, 'documentos/contrato_01.pdf');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ordenes_compra`
--

CREATE TABLE `detalle_ordenes_compra` (
  `id_detalle_compra` int(11) NOT NULL,
  `id_orden_compra` int(11) NOT NULL,
  `id_articulo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_ordenes_compra`
--

INSERT INTO `detalle_ordenes_compra` (`id_detalle_compra`, `id_orden_compra`, `id_articulo`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(5, 2, 8, 1, 16000.00, 16000.00),
(6, 2, 8, 1, 18000.00, 18000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id_detalle_venta` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_articulo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id_detalle_venta`, `id_venta`, `id_articulo`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 11, 11, 2, 6000.00, 12000.00),
(2, 11, 8, 6, 30000.00, 180000.00),
(3, 11, 9, 4, 44900.00, 179600.00),
(4, 12, 2, 2, 18000.00, 36000.00),
(5, 13, 12, 3, 6500.00, 19500.00),
(6, 13, 9, 1, 44900.00, 44900.00),
(7, 13, 7, 1, 18000.00, 18000.00),
(8, 13, 2, 1, 18000.00, 18000.00),
(9, 14, 12, 3, 6500.00, 19500.00),
(10, 14, 11, 1, 7000.00, 7000.00),
(11, 14, 9, 1, 44900.00, 44900.00),
(12, 14, 8, 1, 30000.00, 30000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `id_empleado` int(11) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(120) DEFAULT NULL,
  `cargo` varchar(50) DEFAULT NULL,
  `estado_laboral` enum('activo','inactivo','retirado') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`id_empleado`, `documento`, `nombres`, `apellidos`, `telefono`, `correo`, `cargo`, `estado_laboral`) VALUES
(2, '1030622246', 'William', 'Lopez', '3204955691', 'williamangelito11@gmail.com', 'Administrador', 'activo'),
(3, '1111203801', 'Erika', 'Benitez', '3046445039', 'benitezerikandrea03@gmail.com', 'Diseñadora', 'activo'),
(9, '1010123456', 'Daniel', 'Lopez', '3001234567', 'ejemplo@correo.com', 'Operario', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id_movimiento` int(11) NOT NULL,
  `id_articulo` int(11) NOT NULL,
  `tipo_movimiento` enum('entrada','salida') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` varchar(150) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id_movimiento`, `id_articulo`, `tipo_movimiento`, `cantidad`, `motivo`, `fecha`, `id_usuario`) VALUES
(1, 8, 'salida', 1, 'mercancia dañada', '2026-10-04 02:09:12', 3),
(2, 11, 'salida', 2, 'Venta #11', '2026-10-04 22:48:41', 3),
(3, 8, 'salida', 6, 'Venta #11', '2026-10-04 22:48:41', 3),
(4, 9, 'salida', 4, 'Venta #11', '2026-10-04 22:48:41', 3),
(5, 2, 'salida', 2, 'Venta #12', '2026-10-05 02:27:08', 1),
(6, 12, 'salida', 3, 'Venta #13', '2026-10-05 17:46:20', 3),
(7, 9, 'salida', 1, 'Venta #13', '2026-10-05 17:46:20', 3),
(8, 7, 'salida', 1, 'Venta #13', '2026-10-05 17:46:20', 3),
(9, 2, 'salida', 1, 'Venta #13', '2026-10-05 17:46:20', 3),
(10, 12, 'salida', 3, 'Venta #14', '2026-10-05 19:23:24', 3),
(11, 11, 'salida', 1, 'Venta #14', '2026-10-05 19:23:24', 3),
(12, 9, 'salida', 1, 'Venta #14', '2026-10-05 19:23:24', 3),
(13, 8, 'salida', 1, 'Venta #14', '2026-10-05 19:23:24', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_compra`
--

CREATE TABLE `ordenes_compra` (
  `id_orden_compra` int(11) NOT NULL,
  `id_solicitud` int(11) DEFAULT NULL,
  `id_proveedor` int(11) NOT NULL,
  `fecha_emision` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('emitida','recibida','cancelada') DEFAULT 'emitida'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ordenes_compra`
--

INSERT INTO `ordenes_compra` (`id_orden_compra`, `id_solicitud`, `id_proveedor`, `fecha_emision`, `subtotal`, `descuento`, `total`, `estado`) VALUES
(2, NULL, 1, '2026-10-03', 34000.00, 0.00, 34000.00, 'recibida'),
(16, NULL, 1, '2026-10-03', 0.00, 0.00, 0.00, 'emitida');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nit_cedula` varchar(20) NOT NULL,
  `razon_social` varchar(100) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_proveedor`, `nit_cedula`, `razon_social`, `contacto`, `telefono`, `email`) VALUES
(1, '1234567890', 'Textiles S.A.S. (Actualizado)', 'Pedro Pérez', '3101234567', 'contacto@textilessas.com'),
(4, '900999888-1', 'Distribuidora de Hilos y Cierres S.A.S.', 'Carlos Ramírez', '3159876543', 'ventas@hilosycierres.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`, `descripcion`) VALUES
(1, 'Administrador', 'Jefe de fábrica con acceso total'),
(2, 'Vendedor', 'Encargado de registrar pedidos de venta'),
(3, 'Contador', 'Gestión de comprobantes e informes financieros'),
(4, 'Especialista en Inventario', 'Control de existencias y entradas/salidas'),
(5, 'Encargado de Bodega', 'Recepción de compras y despacho de insumos'),
(6, 'Supervisor', 'encargado de los invenarios de Bodega'),
(7, 'Diseñadora de Modas', 'Encargado de diseñar y patronar las prendas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_compra`
--

CREATE TABLE `solicitudes_compra` (
  `id_solicitud` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_solicitud` date NOT NULL,
  `fecha_deseada` date DEFAULT NULL,
  `estado` enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes_compra`
--

INSERT INTO `solicitudes_compra` (`id_solicitud`, `id_usuario`, `fecha_solicitud`, `fecha_deseada`, `estado`) VALUES
(1, 3, '2026-10-03', '2026-10-05', 'pendiente'),
(3, 4, '2026-10-04', '2026-10-06', 'pendiente'),
(4, 4, '2026-10-06', '2026-10-09', 'aprobada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `email` varchar(120) NOT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `nombre_usuario`, `clave`, `email`, `celular`, `estado`, `fecha_creacion`) VALUES
(1, 4, 'Valery Sofia', '$2y$10$tBzbcgXrbGtMsTasIskPve.pzE9Ao5gcJycz3jgI8QpmDIOHU65x6', 'valerysofia@kiddyline.com', '3001234567', 'activo', '2026-08-27 03:34:02'),
(2, 2, 'Daniel Josue', '$2y$10$h9U0qUyJ7unnM.hiOB7xmuzaDOUc7s2BRFErJil2e0isdvAwYZN.S', 'danieljosue@kiddyline.com', '3009876544', 'activo', '2026-08-27 03:34:02'),
(3, 1, 'William Lopez', '$2y$10$agNwjWlWEVlWzz1CQD94Te7becCtN3oG5CgQgA9RHpdJvjx3ojeA.', 'williamangelito11@gmail.com', '3204955691', 'activo', '2026-09-23 09:04:52'),
(4, 7, 'Erika Benitez', '$2y$10$hdVTZD7NR6IfjEXD/tqJSO9h14/rO8uHISjXrMpzNbwB5KgyxCzA6', 'benitezerikandrea03@gmail.com', '3046445039', 'activo', '2026-10-04 01:23:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_venta` timestamp NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL,
  `impuesto` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('completada','anulada') DEFAULT 'completada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `id_cliente`, `id_usuario`, `fecha_venta`, `subtotal`, `impuesto`, `total`, `estado`) VALUES
(11, 3, 3, '2026-10-04 22:48:41', 371600.00, 70604.00, 442204.00, 'completada'),
(12, 1, 1, '2026-10-05 02:27:08', 36000.00, 6840.00, 42840.00, 'completada'),
(13, 1, 3, '2026-10-05 17:46:20', 100400.00, 19076.00, 119476.00, 'completada'),
(14, 1, 3, '2026-10-05 19:23:23', 101400.00, 19266.00, 120666.00, 'completada');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulos`
--
ALTER TABLE `articulos`
  ADD PRIMARY KEY (`id_articulo`),
  ADD UNIQUE KEY `codigo_referencia` (`codigo_referencia`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `documento` (`documento`);

--
-- Indices de la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  ADD PRIMARY KEY (`id_comprobante`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_orden_compra` (`id_orden_compra`);

--
-- Indices de la tabla `contratos_documentos`
--
ALTER TABLE `contratos_documentos`
  ADD PRIMARY KEY (`id_contrato`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `detalle_ordenes_compra`
--
ALTER TABLE `detalle_ordenes_compra`
  ADD PRIMARY KEY (`id_detalle_compra`),
  ADD KEY `id_orden_compra` (`id_orden_compra`),
  ADD KEY `id_articulo` (`id_articulo`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id_detalle_venta`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_articulo` (`id_articulo`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id_empleado`),
  ADD UNIQUE KEY `documento` (`documento`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_articulo` (`id_articulo`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  ADD PRIMARY KEY (`id_orden_compra`),
  ADD KEY `id_solicitud` (`id_solicitud`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`),
  ADD UNIQUE KEY `nit_cedula` (`nit_cedula`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `solicitudes_compra`
--
ALTER TABLE `solicitudes_compra`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `articulos`
--
ALTER TABLE `articulos`
  MODIFY `id_articulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  MODIFY `id_comprobante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `contratos_documentos`
--
ALTER TABLE `contratos_documentos`
  MODIFY `id_contrato` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `detalle_ordenes_compra`
--
ALTER TABLE `detalle_ordenes_compra`
  MODIFY `id_detalle_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id_empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  MODIFY `id_orden_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `solicitudes_compra`
--
ALTER TABLE `solicitudes_compra`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `articulos`
--
ALTER TABLE `articulos`
  ADD CONSTRAINT `articulos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`);

--
-- Filtros para la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  ADD CONSTRAINT `comprobantes_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`),
  ADD CONSTRAINT `comprobantes_ibfk_2` FOREIGN KEY (`id_orden_compra`) REFERENCES `ordenes_compra` (`id_orden_compra`);

--
-- Filtros para la tabla `contratos_documentos`
--
ALTER TABLE `contratos_documentos`
  ADD CONSTRAINT `contratos_documentos_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_ordenes_compra`
--
ALTER TABLE `detalle_ordenes_compra`
  ADD CONSTRAINT `detalle_ordenes_compra_ibfk_1` FOREIGN KEY (`id_orden_compra`) REFERENCES `ordenes_compra` (`id_orden_compra`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_ordenes_compra_ibfk_2` FOREIGN KEY (`id_articulo`) REFERENCES `articulos` (`id_articulo`);

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`id_articulo`) REFERENCES `articulos` (`id_articulo`);

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`id_articulo`) REFERENCES `articulos` (`id_articulo`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  ADD CONSTRAINT `ordenes_compra_ibfk_1` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes_compra` (`id_solicitud`),
  ADD CONSTRAINT `ordenes_compra_ibfk_2` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`);

--
-- Filtros para la tabla `solicitudes_compra`
--
ALTER TABLE `solicitudes_compra`
  ADD CONSTRAINT `solicitudes_compra_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
