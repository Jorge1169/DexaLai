-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 29-08-2025 a las 17:24:18
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `lai`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacen`
--

DROP TABLE IF EXISTS `almacen`;
CREATE TABLE IF NOT EXISTS `almacen` (
  `id_alnaceb` int NOT NULL AUTO_INCREMENT,
  `id_venta` varchar(255) DEFAULT NULL,
  `id_compra` varchar(255) DEFAULT NULL,
  `id_prod` varchar(255) DEFAULT NULL,
  `entrada` varchar(255) DEFAULT NULL,
  `salida` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '1',
  `zona` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_alnaceb`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `almacen`
--

INSERT INTO `almacen` (`id_alnaceb`, `id_venta`, `id_compra`, `id_prod`, `entrada`, `salida`, `fecha`, `id_user`, `status`, `zona`) VALUES
(1, NULL, '1', '4', '24740.00', NULL, '2025-07-29 00:25:07', '1', '1', '1'),
(2, NULL, '2', '11', '21300.00', NULL, '2025-07-29 00:33:02', '1', '1', '1'),
(3, '1', NULL, '4', NULL, '24740', '2025-07-29 00:38:03', '1', '1', '1'),
(4, '2', NULL, '11', NULL, '21300', '2025-07-29 23:08:32', '1', '1', '1'),
(23, NULL, '16', '3', '26270', NULL, '2025-08-22 22:03:30', '1', '1', '1'),
(24, '13', NULL, '3', NULL, '26270', '2025-08-22 22:03:30', '1', '1', '1'),
(25, NULL, '17', '9', '17660', NULL, '2025-08-25 20:33:54', '1', '1', '1'),
(26, '14', NULL, '9', NULL, '17660', '2025-08-25 20:33:54', '1', '1', '1'),
(27, NULL, '18', '3', '26040', NULL, '2025-08-28 16:30:18', '1', '1', '1'),
(28, '15', NULL, '3', NULL, '26040', '2025-08-28 16:30:18', '1', '1', '1'),
(29, NULL, '19', '11', '22700', NULL, '2025-08-28 16:30:18', '1', '1', '1'),
(30, '16', NULL, '11', NULL, '22700', '2025-08-28 16:30:18', '1', '1', '1'),
(31, NULL, '20', '9', '21140', NULL, '2025-08-28 16:30:18', '1', '1', '1'),
(32, '17', NULL, '9', NULL, '21140', '2025-08-28 16:30:18', '1', '1', '1'),
(33, NULL, '21', '11', '24400', NULL, '2025-08-28 16:30:18', '1', '1', '1'),
(34, '18', NULL, '11', NULL, '24400', '2025-08-28 16:30:18', '1', '1', '1'),
(35, NULL, '22', '9', '20055', NULL, '2025-08-28 16:30:18', '1', '1', '1'),
(36, '19', NULL, '9', NULL, '20055', '2025-08-28 16:30:18', '1', '1', '1'),
(37, NULL, '23', '3', '29430', NULL, '2025-08-28 16:30:18', '1', '1', '1'),
(38, '20', NULL, '3', NULL, '29430', '2025-08-28 16:30:18', '1', '1', '1'),
(39, NULL, '24', '11', '23200', NULL, '2025-08-28 16:30:18', '1', '1', '1'),
(40, '21', NULL, '11', NULL, '23200', '2025-08-28 16:30:18', '1', '1', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE IF NOT EXISTS `clientes` (
  `id_cli` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) DEFAULT NULL,
  `cod` varchar(255) DEFAULT NULL,
  `rs` varchar(255) DEFAULT NULL,
  `rfc` varchar(255) DEFAULT NULL,
  `c_contable` varchar(255) DEFAULT NULL,
  `tpersona` varchar(255) DEFAULT NULL,
  `tpcliente` varchar(255) DEFAULT NULL,
  `m_pago` varchar(255) DEFAULT NULL,
  `f_pago` varchar(255) DEFAULT NULL,
  `banco` varchar(255) DEFAULT NULL,
  `cfdi` varchar(255) DEFAULT NULL,
  `dir_a` varchar(255) DEFAULT NULL,
  `nom_cuenta` varchar(255) DEFAULT NULL,
  `con_pago` varchar(255) DEFAULT NULL,
  `obs` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '1',
  `zona` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_cli`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cli`, `nombre`, `cod`, `rs`, `rfc`, `c_contable`, `tpersona`, `tpcliente`, `m_pago`, `f_pago`, `banco`, `cfdi`, `dir_a`, `nom_cuenta`, `con_pago`, `obs`, `fecha`, `id_user`, `status`, `zona`) VALUES
(1, 'SAN PABLO', 'E002', 'EMPAQUES MODERNOS SAN PABLO', 'EMS810717R34', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 23:51:25', '1', '1', '1'),
(2, 'BIO PAPPEL', 'B001', 'BIO PAPPEL', 'CDU820122JFA', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 23:57:05', '1', '1', '1'),
(3, 'ULTRA', 'P007', 'PAPELES ULTRA', 'PUL000114K69', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 23:57:53', '1', '1', '1'),
(4, 'SMURFIT', 'S002', 'SMURFIT CARTON Y PAPEL DE MEXICO', 'SCP900125TT8', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 23:58:25', '1', '1', '1'),
(5, 'NEVADO', 'P008', 'PAPELERA DEL NEVADO', 'PNE780427787', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 23:58:54', '1', '1', '1'),
(6, 'DEXA', 'D207', 'DISTRIBUIDORA DE EMPAQUES', 'DEM970414561', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 23:59:24', '1', '1', '1'),
(7, 'SAN PABLO', 'E008', 'EMPAQUES MODERNOS SAN PABLO', 'EMS810717R34', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-29 00:00:26', '1', '1', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

DROP TABLE IF EXISTS `compras`;
CREATE TABLE IF NOT EXISTS `compras` (
  `id_compra` int NOT NULL AUTO_INCREMENT,
  `fact` varchar(255) DEFAULT NULL,
  `d_prov` varchar(255) DEFAULT NULL,
  `factura` varchar(255) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `id_prov` varchar(255) DEFAULT NULL,
  `id_direc` varchar(255) DEFAULT NULL,
  `id_transp` varchar(255) DEFAULT NULL,
  `id_prod` varchar(255) DEFAULT NULL,
  `tara` varchar(255) NOT NULL,
  `bruto` varchar(255) DEFAULT NULL,
  `neto` varchar(255) DEFAULT NULL,
  `pres` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `acciones` varchar(255) NOT NULL DEFAULT '0',
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT '1',
  `zona` varchar(255) DEFAULT NULL,
  `ex` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_compra`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `compras`
--

INSERT INTO `compras` (`id_compra`, `fact`, `d_prov`, `factura`, `nombre`, `id_prov`, `id_direc`, `id_transp`, `id_prod`, `tara`, `bruto`, `neto`, `pres`, `fecha`, `acciones`, `id_user`, `status`, `zona`, `ex`) VALUES
(1, '23652', NULL, NULL, 'DKL MTY', '1', '6', '1', '4', '0', '24740', '24740.00', '3.60', '2025-07-08 06:00:00', '0', '1', '1', '1', '0'),
(2, '11955/167000', 'archivos/LAISA/2025/07/EA_B186_935_250728', '935', 'IXTAC', '1', '4', '29', '11', '0', '21300', '21300.00', '3.60', '2025-07-24 06:00:00', '0', '1', '1', '1', '0'),
(16, '20762', NULL, NULL, '20762', '1', '5', '28', '3', '0.00', '26270', '26270', '2.8', '2025-07-30 06:00:00', '1', '1', '1', '1', '2'),
(17, '114376', NULL, NULL, '114376', '1', '11', '4', '9', '0.00', '17660', '17660', '3.6', '2025-07-25 06:00:00', '1', '1', '1', '1', '2'),
(18, '20758', NULL, NULL, '20758', '1', '5', '10', '3', '0.00', '26040', '26040', '2.8', '2025-07-25 06:00:00', '0', '1', '1', '1', '2'),
(19, '11959-167134', NULL, NULL, '11959-167134', '1', '4', '10', '11', '0.00', '22700', '22700', '3.6', '2025-07-28 06:00:00', '0', '1', '1', '1', '2'),
(20, '114378', NULL, NULL, '114378', '1', '11', '4', '9', '0.00', '21140', '21140', '3.6', '2025-07-25 06:00:00', '0', '1', '1', '1', '2'),
(21, '11965-167336', NULL, NULL, '11965-167336', '1', '4', '10', '11', '0.00', '24400', '24400', '3.6', '2025-07-30 06:00:00', '0', '1', '1', '1', '2'),
(22, '10135', NULL, NULL, '10135', '1', '2', '29', '9', '0.00', '20055', '20055', '3.6', '2025-07-30 06:00:00', '0', '1', '1', '1', '2'),
(23, '20768', NULL, NULL, '20768', '1', '5', '10', '3', '0.00', '29430', '29430', '2.8', '2025-07-31 06:00:00', '0', '1', '1', '1', '2'),
(24, '11966-167514', NULL, NULL, '11966-167514', '1', '4', '10', '11', '0.00', '23200', '23200', '3.6', '2025-07-31 06:00:00', '0', '1', '1', '1', '2');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

DROP TABLE IF EXISTS `direcciones`;
CREATE TABLE IF NOT EXISTS `direcciones` (
  `id_direc` int NOT NULL AUTO_INCREMENT,
  `id_us` varchar(255) DEFAULT NULL,
  `id_prov` varchar(255) DEFAULT NULL,
  `cod_al` varchar(255) DEFAULT NULL,
  `noma` varchar(255) DEFAULT NULL,
  `atencion` varchar(255) DEFAULT NULL,
  `calle` varchar(255) DEFAULT NULL,
  `c_postal` varchar(255) DEFAULT NULL,
  `numext` varchar(255) DEFAULT NULL,
  `numint` varchar(255) DEFAULT NULL,
  `pais` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `mun` varchar(255) DEFAULT NULL,
  `colonia` varchar(255) DEFAULT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `obs` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT '1',
  PRIMARY KEY (`id_direc`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `direcciones`
--

INSERT INTO `direcciones` (`id_direc`, `id_us`, `id_prov`, `cod_al`, `noma`, `atencion`, `calle`, `c_postal`, `numext`, `numint`, `pais`, `estado`, `mun`, `colonia`, `tel`, `email`, `obs`, `status`) VALUES
(1, NULL, '1', 'I-107CDJUA', 'CD JUAREZ', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(2, NULL, '1', 'I-107GUA', 'GUADALAJARA', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(3, NULL, '1', 'I-107APO', 'IP APODACA', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(4, NULL, '1', 'I-107IXTAC', 'IXTACZOQUITLAN', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(5, NULL, '1', 'I-107MOCH', 'LOS MOCHIS', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(6, NULL, '1', 'I-107MTY', 'MONTERREY', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(7, NULL, '1', 'I-107PUE', 'PUEBLA', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(8, NULL, '1', 'I-107REY	', 'REYNOSA', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(9, NULL, '1', 'I-107SNJI', 'SAN JOSE ITURBIDE', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(10, NULL, '1', 'I-107SIL', 'SILAO', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(11, NULL, '1', 'I-107TOL', 'TOLUCA', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(12, '1', NULL, 'E002', 'EMPAQUES MODERNOS SAN PABLO', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(13, '2', NULL, 'B001', 'BIO PAPPEL', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(14, '3', NULL, 'P007', 'PAPELES ULTRA', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(15, '4', NULL, 'S002', 'SMURFIT CARTON Y PAPEL DE MEXICO', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(16, '5', NULL, 'P008', 'PAPELERA DEL NEVADO', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(17, '6', NULL, 'D207', 'DISTRIBUIDORA DE EMPAQUES', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
(18, '7', NULL, 'E008', 'EMPAQUES MODERNOS SAN PABLO', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE IF NOT EXISTS `productos` (
  `id_prod` int NOT NULL AUTO_INCREMENT,
  `nom_pro` varchar(255) DEFAULT NULL,
  `cod` varchar(255) DEFAULT NULL,
  `lin` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '1',
  `zona` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_prod`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_prod`, `nom_pro`, `cod`, `lin`, `fecha`, `id_user`, `status`, `zona`) VALUES
(1, 'DKL CD JUAREZ', '15DKLCDJUA', '15 DKL', '2025-07-29 06:05:00', '1', '1', '1'),
(2, 'DKL GUADALAJARA', '15DKLGUA', '15 DKL', '2025-07-29 06:06:19', '1', '1', '1'),
(3, 'DKL MOCHIS', '15DKLMOCH', '15 DKL', '2025-07-29 06:06:36', '1', '1', '1'),
(4, 'DKL MONTERREY', '15DKLMTY', '15 DKL', '2025-07-29 06:06:52', '1', '1', '1'),
(5, 'DKL PUEBLA', '15DKLPUE', '15 DKL', '2025-07-29 06:07:05', '1', '1', '1'),
(6, 'DKL SAN JOSE ITURBIDE', '15DKLSNJI', '15 DKL', '2025-07-29 06:07:17', '1', '1', '1'),
(7, 'DKL SILAO', '15DKLSIL', '15 DKL', '2025-07-29 06:07:28', '1', '1', '1'),
(8, 'DKL SILAO ENCERADO', '15DKLSILENC', '15 DKL', '2025-07-29 06:07:39', '1', '1', '1'),
(9, 'DKL TOLUCA', '15DKLTOL', '15 DKL', '2025-07-29 06:07:50', '1', '1', '1'),
(10, 'DKL XALAPA', '15DKLXAL', '15 DKL', '2025-07-29 06:11:59', '1', '1', '1'),
(11, 'DKL IXTAC EMPACADO PRE-CONSUMO', '15DKLIXTACEMPPC	', '15 DKL', '2025-07-29 06:12:16', '1', '1', '1'),
(12, 'DKL IXTAC GRANEL PRE-CONSUMO', '15DKLIXTACGPC', '15 DKL', '2025-07-29 06:13:25', '1', '1', '1'),
(13, 'DKL IXTAC RE-EMPACADO PRE-CONSUMO', '15DKLIXTACREPC', '15 DKL', '2025-07-29 06:13:37', '1', '1', '1'),
(14, 'DKL SAN JOSE IT. GRANEL PRE-CONSUMO', '15DKLSNJIGPC', '15 DKL', '2025-07-29 06:13:48', '1', '1', '1'),
(15, 'DKL TOLUCA GRANEL PRE-CONSUMO', '15DKLTOLGPC', '15 DKL', '2025-07-29 06:13:59', '1', '1', '1'),
(16, 'DKL TOLUCA PRE-CONSUMO', '15DKLTOLPC', '15 DKL', '2025-07-29 06:14:12', '1', '1', '1'),
(17, 'DKL MOCHIS PRE-CONSUMO', '15DKLMOCHPC', '15 DKL', '2025-07-29 06:14:25', '1', '1', '1'),
(18, 'DKL MOCHIS GRANEL PRE-CONSUMO', '15DKLMOCHGPC', '15 DKL', '2025-07-29 06:14:43', '1', '1', '1'),
(19, 'DKL MONTERREY PRE-CONSUMO', '15DKLMTYPC', '15 DKL', '2025-07-29 06:14:55', '1', '1', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
CREATE TABLE IF NOT EXISTS `proveedores` (
  `id_prov` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) DEFAULT NULL,
  `cod` varchar(255) DEFAULT NULL,
  `rs` varchar(255) DEFAULT NULL,
  `rfc` varchar(255) DEFAULT NULL,
  `c_contable` varchar(255) DEFAULT NULL,
  `tpersona` varchar(255) DEFAULT NULL,
  `tproveedor` varchar(255) DEFAULT NULL,
  `m_pago` varchar(255) DEFAULT NULL,
  `f_pago` varchar(255) DEFAULT NULL,
  `banco` varchar(255) DEFAULT NULL,
  `cfdi` varchar(255) DEFAULT NULL,
  `dir_a` varchar(255) DEFAULT NULL,
  `nom_cuenta` varchar(255) DEFAULT NULL,
  `con_pago` varchar(255) DEFAULT NULL,
  `obs` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT '1',
  `zona` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_prov`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_prov`, `nombre`, `cod`, `rs`, `rfc`, `c_contable`, `tpersona`, `tproveedor`, `m_pago`, `f_pago`, `banco`, `cfdi`, `dir_a`, `nom_cuenta`, `con_pago`, `obs`, `fecha`, `id_user`, `status`, `zona`) VALUES
(1, 'IP', 'I-107', 'INTERNATIONAL PAPER MEXICO COMPANY', 'IPH140130GE7', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 23:36:54', '1', '1', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transportes`
--

DROP TABLE IF EXISTS `transportes`;
CREATE TABLE IF NOT EXISTS `transportes` (
  `id_transp` int NOT NULL AUTO_INCREMENT,
  `placas` varchar(255) DEFAULT NULL,
  `razon_so` varchar(255) DEFAULT NULL,
  `linea` varchar(255) DEFAULT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `chofer` varchar(255) DEFAULT NULL,
  `placas_caja` varchar(255) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `zona` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_transp`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `transportes`
--

INSERT INTO `transportes` (`id_transp`, `placas`, `razon_so`, `linea`, `tipo`, `chofer`, `placas_caja`, `correo`, `fecha`, `id_user`, `status`, `zona`) VALUES
(1, '123ABC', NULL, 'PROPIA', 'TRAILER', 'N/S', '456DEF', NULL, '2025-07-29 00:04:03', '1', '0', '1'),
(2, '1256AA', NULL, 'PROPIA', 'TORTON', 'N/S', '', NULL, '2025-08-14 18:53:07', '1', '0', '1'),
(3, 'N/A', NULL, 'N/A', 'TRAILER', 'N/A', '', NULL, '2025-08-18 21:41:37', '1', '0', '1'),
(4, 'A-420', 'ARREOLA RUBIO RIGOBERTO', 'PROPIA', 'TRAILER', '', '', 'jorge.d.victorio11@gmail.com', '2025-08-20 18:18:14', '1', '1', '1'),
(5, 'S-408', 'SALAZAR GONZALEZ ALFONSO', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(6, 'M-454', 'MORALES MUNOZ MARIANA', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(7, 'M-446', 'MUNGUIA VASQUEZ MARIA DEL CARMEN', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(8, 'T-232', 'TRANSPOMARE SA DE CV', 'PROPIA', 'TRAILER', '', '', 'sistemas.jorge1105@gmail.com', '2025-08-20 18:34:26', '1', '1', '1'),
(9, 'T-229', 'TRANSPORTES VIGNOLA SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(10, 'T-230', 'TEMM TRUCK SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(11, 'T-235', 'TRANSPORTE Y LOGISTICA BUZMYR DE MEXICO SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(12, 'T-236', 'TRANSPORTES BENDOL SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(13, 'T-233', 'TRANSPORCART SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(14, 'T-234', 'TRANSPORTADORA LOGISTICA DE COACALCO SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(15, 'S-421', 'SERVICIOS INTEGRALES DE LOGISTICA NURIB SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(16, 'S-422', 'SOLUCIONES EN TRANSPORTE ROMA SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(17, 'T-040', 'TRANSPORTES ALIANO SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(18, 'T-239', 'TRANSPORTES PIGASA SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(19, 'T-240', 'TRANSPORTES RAMIREZ MARQUEZ SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(20, 'T-237', 'TRANSPORTES MARQUEZ DE TEPOTZOTLAN SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(21, 'S-407', 'SALAZAR ESCOBAR JULIO CESAR', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(22, 'S-409', 'SANCHEZ SANCHEZ JUANA', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(23, 'C-600', 'COLOSOS TRANSPORTES SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(24, 'C-598', 'CHAIRES BARRIENTOS OSCAR', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(25, 'A-424', 'AUTOTRANSPORTES NEW PICK SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(26, 'A-434', 'AUTOTRANSPORTES DEL REAL SA DE CV', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(27, 'H-165', 'HERNANDEZ GARRIDO MANUEL MARCELINO', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(28, 'D-227', 'DAVILA PALOMINO RAUL', 'PROPIA', 'TRAILER', '', '', NULL, '2025-08-20 18:34:26', '1', '1', '1'),
(29, 'B-186', 'N/S', 'PROPIA', 'TRAILER', 'N/S', 'N/S', 'sistemas.jorge1105@gmail.com', '2025-08-21 17:42:16', '1', '1', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `usuario` varchar(255) DEFAULT NULL,
  `pass` varchar(255) DEFAULT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `a` varchar(255) DEFAULT '0',
  `b` varchar(255) NOT NULL DEFAULT '0',
  `c` varchar(255) NOT NULL DEFAULT '0',
  `d` varchar(255) NOT NULL DEFAULT '0',
  `e` varchar(255) NOT NULL DEFAULT '0',
  `a1` varchar(255) NOT NULL DEFAULT '0',
  `b1` varchar(255) NOT NULL DEFAULT '0',
  `c1` varchar(255) NOT NULL DEFAULT '0',
  `d1` varchar(255) NOT NULL DEFAULT '0',
  `e1` varchar(255) NOT NULL DEFAULT '0',
  `zona` varchar(255) DEFAULT NULL,
  `af` varchar(255) NOT NULL DEFAULT '0',
  `acr` varchar(255) NOT NULL DEFAULT '0',
  `acc` varchar(255) NOT NULL DEFAULT '0',
  `status` varchar(255) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_user`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_user`, `nombre`, `correo`, `usuario`, `pass`, `tipo`, `fecha`, `a`, `b`, `c`, `d`, `e`, `a1`, `b1`, `c1`, `d1`, `e1`, `zona`, `af`, `acr`, `acc`, `status`) VALUES
(1, 'Jorge Victorio', 'sistemas2@glama.com.mx', 'ADMIN', '06af401c13fb904e491347d1a55feac3', '100', '2025-07-22 17:54:42', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '1', '1', '1', '1'),
(2, 'Daniel Arroyo', 'jorge.d.victorio11@gmail.com', 'DANIEL', '827ccb0eea8a706c4c34a16891f84e7b', '10', '2025-07-23 22:23:07', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '0', '0', '1'),
(3, 'Arturo Islas', 'aislas@glama.com.mx', 'MASTER', '2b56c292ea9856b33766eca6f934fc48', '100', '2025-07-26 05:58:28', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '1', '1', '1', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

DROP TABLE IF EXISTS `ventas`;
CREATE TABLE IF NOT EXISTS `ventas` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `fact` varchar(255) DEFAULT NULL,
  `factura` varchar(255) DEFAULT NULL,
  `d_prov` varchar(255) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `id_cli` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `id_direc` varchar(255) DEFAULT NULL,
  `id_compra` varchar(255) DEFAULT NULL,
  `id_prod` varchar(255) DEFAULT NULL,
  `costo_flete` varchar(255) DEFAULT NULL,
  `flete` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `fact_fle` varchar(255) DEFAULT NULL,
  `d_fletero` varchar(255) DEFAULT NULL,
  `tara` varchar(255) DEFAULT NULL,
  `bruto` varchar(255) DEFAULT NULL,
  `Neto` varchar(255) DEFAULT NULL,
  `peso_cliente` varchar(255) DEFAULT NULL,
  `precio` varchar(255) DEFAULT NULL,
  `im_tras_inv` varchar(255) DEFAULT NULL,
  `im_rete_inv` varchar(255) DEFAULT NULL,
  `total_inv` varchar(255) DEFAULT NULL,
  `rfc_inv` varchar(255) DEFAULT NULL,
  `aliasInv` varchar(255) DEFAULT NULL,
  `folio_contra` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT '1',
  `acciones` varchar(255) NOT NULL DEFAULT '0',
  `zona` varchar(255) DEFAULT NULL,
  `ex` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_venta`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `fact`, `factura`, `d_prov`, `nombre`, `id_cli`, `id_direc`, `id_compra`, `id_prod`, `costo_flete`, `flete`, `fact_fle`, `d_fletero`, `tara`, `bruto`, `Neto`, `peso_cliente`, `precio`, `im_tras_inv`, `im_rete_inv`, `total_inv`, `rfc_inv`, `aliasInv`, `folio_contra`, `fecha`, `id_user`, `status`, `acciones`, `zona`, `ex`) VALUES
(1, '23652', 'A30914', NULL, 'SAN PABLO', '7', '18', '1', '4', '0', '1', NULL, NULL, NULL, NULL, NULL, '24740', '4.50', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-09 06:00:00', '1', '1', '0', '1', '0'),
(2, '11955/167000', 'A30898', NULL, 'BIO PAPPEL', '2', '13', '2', '11', '15005', '29', '935', 'archivos/LAISA/2025/07/EA_B186_935_250728', NULL, NULL, NULL, '21300', '5.29', '2400.800', '600.200', '16805.600', 'LAI4802211H2', NULL, NULL, '2025-07-25 06:00:00', '1', '1', '0', '1', '0'),
(13, '20767', NULL, NULL, '20767', '3', '14', '16', '3', '50701.1', '28', '19775', 'archivos/LAISA/2025/07/EA_D227_19775_250730', NULL, NULL, NULL, '26270', '5.3', '8112.180', '2028.040', '56785.240', 'LAI4802211H2', NULL, NULL, '2025-07-30 06:00:00', '1', '1', '1', '1', '2'),
(14, '114376', '', NULL, '114376', '7', '18', '17', '9', '7505', '4', '8671', 'archivos/LAISA/2025/08/EA_A420_8671_250804', NULL, NULL, NULL, '17660', '5.37', '1200.800', '300.200', '8405.600', 'LAI4802211H2', 'LAISA', '17654', '2025-07-25 06:00:00', '1', '1', '1', '1', '2'),
(15, '20758', 'A30915', NULL, '20758', '3', '14', '18', '3', '1930', '10', NULL, NULL, NULL, NULL, NULL, '26040', '5.3', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 06:00:00', '1', '1', '0', '1', '2'),
(16, '11959-167134', 'A30918', NULL, '11959-167134', '3', '14', '19', '11', '14350', '10', NULL, NULL, NULL, NULL, NULL, '22700', '5.3', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-28 06:00:00', '1', '1', '0', '1', '2'),
(17, '114378', 'A30931', NULL, '114378', '7', '18', '20', '9', '7505', '4', NULL, NULL, NULL, NULL, NULL, '21140', '5.37', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 06:00:00', '1', '1', '0', '1', '2'),
(18, '11965-167336', 'A30932', NULL, '11965-167336', '3', '14', '21', '11', '14350', '10', NULL, NULL, NULL, NULL, NULL, '24400', '5.3', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-30 06:00:00', '1', '1', '0', '1', '2'),
(19, '10135', 'A30945', NULL, '10135', '2', '13', '22', '9', '15005', '29', NULL, NULL, NULL, NULL, NULL, '20055', '5.29', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-30 06:00:00', '1', '1', '0', '1', '2'),
(20, '20768', 'A30992', NULL, '20768', '3', '14', '23', '3', '1930', '10', NULL, NULL, NULL, NULL, NULL, '29430', '5.3', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-31 06:00:00', '1', '1', '0', '1', '2'),
(21, '11966-167514', 'A30993', NULL, '11966-167514', '3', '14', '24', '11', '14350', '10', NULL, NULL, NULL, NULL, NULL, '23200', '5.3', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-31 06:00:00', '1', '1', '0', '1', '2');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zonas`
--

DROP TABLE IF EXISTS `zonas`;
CREATE TABLE IF NOT EXISTS `zonas` (
  `id_zone` int NOT NULL AUTO_INCREMENT,
  `cod` varchar(255) DEFAULT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_zone`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `zonas`
--

INSERT INTO `zonas` (`id_zone`, `cod`, `nom`, `fecha`, `id_user`, `status`) VALUES
(1, 'DKL', 'LAI-DKL', '2025-08-05 21:58:04', '1', '1'),
(2, 'HEI', 'DEXA-HEINEKEN', '2025-08-06 18:36:07', '1', '1');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
