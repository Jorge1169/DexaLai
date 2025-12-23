-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 31-07-2025 a las 23:27:36
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
  PRIMARY KEY (`id_alnaceb`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `almacen`
--

INSERT INTO `almacen` (`id_alnaceb`, `id_venta`, `id_compra`, `id_prod`, `entrada`, `salida`, `fecha`, `id_user`, `status`) VALUES
(1, NULL, '1', '4', '24740.00', NULL, '2025-07-28 18:25:07', '1', '1'),
(2, NULL, '2', '11', '21300.00', NULL, '2025-07-28 18:33:02', '1', '1'),
(3, '1', NULL, '4', NULL, '24740', '2025-07-28 18:38:03', '1', '1'),
(4, '2', NULL, '11', NULL, '21300', '2025-07-29 17:08:32', '1', '1');

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
  PRIMARY KEY (`id_cli`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cli`, `nombre`, `cod`, `rs`, `rfc`, `c_contable`, `tpersona`, `tpcliente`, `m_pago`, `f_pago`, `banco`, `cfdi`, `dir_a`, `nom_cuenta`, `con_pago`, `obs`, `fecha`, `id_user`, `status`) VALUES
(1, 'SAN PABLO', 'E002', 'EMPAQUES MODERNOS SAN PABLO', 'EMS810717R34', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 17:51:25', '3', '1'),
(2, 'BIO PAPPEL', 'B001', 'BIO PAPPEL', 'CDU820122JFA', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 17:57:05', '1', '1'),
(3, 'ULTRA', 'P007', 'PAPELES ULTRA', 'PUL000114K69', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 17:57:53', '1', '1'),
(4, 'SMURFIT', 'S002', 'SMURFIT CARTON Y PAPEL DE MEXICO', 'SCP900125TT8', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 17:58:25', '1', '1'),
(5, 'NEVADO', 'P008', 'PAPELERA DEL NEVADO', 'PNE780427787', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 17:58:54', '1', '1'),
(6, 'DEXA', 'D207', 'DISTRIBUIDORA DE EMPAQUES', 'DEM970414561', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 17:59:24', '1', '1'),
(7, 'SAN PABLO', 'E008', 'EMPAQUES MODERNOS SAN PABLO', 'EMS810717R34', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 18:00:26', '1', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

DROP TABLE IF EXISTS `compras`;
CREATE TABLE IF NOT EXISTS `compras` (
  `id_compra` int NOT NULL AUTO_INCREMENT,
  `fact` varchar(255) DEFAULT NULL,
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
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT '1',
  PRIMARY KEY (`id_compra`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `compras`
--

INSERT INTO `compras` (`id_compra`, `fact`, `factura`, `nombre`, `id_prov`, `id_direc`, `id_transp`, `id_prod`, `tara`, `bruto`, `neto`, `pres`, `fecha`, `id_user`, `status`) VALUES
(1, '23652', NULL, 'DKL MTY', '1', '6', '1', '4', '0', '24740', '24740.00', '3.60', '2025-07-24 06:00:00', '2', '1'),
(2, '11955/167000', NULL, 'IXTAC', '1', '4', '1', '11', '0', '21300', '21300.00', '3.60', '2025-07-25 06:00:00', '1', '1');

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
(4, NULL, '1', 'I-107IXTAC', 'IXTACZOQUITLAN	', 'N/S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '55 5555 5555', '', '', '1'),
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
  PRIMARY KEY (`id_prod`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_prod`, `nom_pro`, `cod`, `lin`, `fecha`, `id_user`, `status`) VALUES
(1, 'DKL CD JUAREZ', '15DKLCDJUA', '15 DKL', '2025-07-29 00:05:00', '1', '1'),
(2, 'DKL GUADALAJARA', '15DKLGUA', '15 DKL', '2025-07-29 00:06:19', '1', '1'),
(3, 'DKL MOCHIS', '15DKLMOCH', '15 DKL', '2025-07-29 00:06:36', '1', '1'),
(4, 'DKL MONTERREY', '15DKLMTY', '15 DKL', '2025-07-29 00:06:52', '1', '1'),
(5, 'DKL PUEBLA', '15DKLPUE', '15 DKL', '2025-07-29 00:07:05', '1', '1'),
(6, 'DKL SAN JOSE ITURBIDE', '15DKLSNJI', '15 DKL', '2025-07-29 00:07:17', '1', '1'),
(7, 'DKL SILAO', '15DKLSIL', '15 DKL', '2025-07-29 00:07:28', '1', '1'),
(8, 'DKL SILAO ENCERADO', '15DKLSILENC', '15 DKL', '2025-07-29 00:07:39', '1', '1'),
(9, 'DKL TOLUCA', '15DKLTOL', '15 DKL', '2025-07-29 00:07:50', '1', '1'),
(10, 'DKL XALAPA', '15DKLXAL', '15 DKL', '2025-07-29 00:11:59', '1', '1'),
(11, 'DKL IXTAC EMPACADO PRE-CONSUMO', '15DKLIXTACEMPPC	', '15 DKL', '2025-07-29 00:12:16', '1', '1'),
(12, 'DKL IXTAC GRANEL PRE-CONSUMO', '15DKLIXTACGPC', '15 DKL', '2025-07-29 00:13:25', '1', '1'),
(13, 'DKL IXTAC RE-EMPACADO PRE-CONSUMO', '15DKLIXTACREPC', '15 DKL', '2025-07-29 00:13:37', '1', '1'),
(14, 'DKL SAN JOSE IT. GRANEL PRE-CONSUMO', '15DKLSNJIGPC', '15 DKL', '2025-07-29 00:13:48', '1', '1'),
(15, 'DKL TOLUCA GRANEL PRE-CONSUMO', '15DKLTOLGPC', '15 DKL', '2025-07-29 00:13:59', '1', '1'),
(16, 'DKL TOLUCA PRE-CONSUMO', '15DKLTOLPC', '15 DKL', '2025-07-29 00:14:12', '1', '1'),
(17, 'DKL MOCHIS PRE-CONSUMO', '15DKLMOCHPC', '15 DKL', '2025-07-29 00:14:25', '1', '1'),
(18, 'DKL MOCHIS GRANEL PRE-CONSUMO', '15DKLMOCHGPC', '15 DKL', '2025-07-29 00:14:43', '1', '1'),
(19, 'DKL MONTERREY PRE-CONSUMO', '15DKLMTYPC', '15 DKL', '2025-07-29 00:14:55', '1', '1');

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
  PRIMARY KEY (`id_prov`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_prov`, `nombre`, `cod`, `rs`, `rfc`, `c_contable`, `tpersona`, `tproveedor`, `m_pago`, `f_pago`, `banco`, `cfdi`, `dir_a`, `nom_cuenta`, `con_pago`, `obs`, `fecha`, `id_user`, `status`) VALUES
(1, 'IP', 'I-107', 'INTERNATIONAL PAPER MEXICO COMPANY', 'IPH140130GE7', NULL, 'moral', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2025-07-28 17:36:54', '2', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transportes`
--

DROP TABLE IF EXISTS `transportes`;
CREATE TABLE IF NOT EXISTS `transportes` (
  `id_transp` int NOT NULL AUTO_INCREMENT,
  `placas` varchar(255) DEFAULT NULL,
  `linea` varchar(255) DEFAULT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `chofer` varchar(255) DEFAULT NULL,
  `placas_caja` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_transp`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `transportes`
--

INSERT INTO `transportes` (`id_transp`, `placas`, `linea`, `tipo`, `chofer`, `placas_caja`, `fecha`, `id_user`, `status`) VALUES
(1, '123ABC', 'PROPIA', 'TRAILER', 'N/S', '456DEF', '2025-07-28 18:04:03', '1', '1');

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
  `status` varchar(255) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_user`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_user`, `nombre`, `correo`, `usuario`, `pass`, `tipo`, `fecha`, `a`, `b`, `c`, `d`, `e`, `a1`, `b1`, `c1`, `d1`, `e1`, `status`) VALUES
(1, 'Jorge Victorio', 'sistemas2@glama.com.mx', 'ADMIN', '06af401c13fb904e491347d1a55feac3', '100', '2025-07-22 17:54:42', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1'),
(2, 'Daniel Arroyo', 'jorge.d.victorio11@gmail.com', 'DANIEL', '25f9e794323b453885f5181f1b624d0b', '10', '2025-07-23 22:23:07', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1'),
(3, 'Arturo Islas', 'aislas@glama.com.mx', 'MASTER', '2b56c292ea9856b33766eca6f934fc48', '100', '2025-07-26 05:58:28', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

DROP TABLE IF EXISTS `ventas`;
CREATE TABLE IF NOT EXISTS `ventas` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `fact` varchar(255) DEFAULT NULL,
  `factura` varchar(255) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `id_cli` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `id_direc` varchar(255) DEFAULT NULL,
  `id_compra` varchar(255) DEFAULT NULL,
  `id_prod` varchar(255) DEFAULT NULL,
  `costo_flete` varchar(255) DEFAULT NULL,
  `flete` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `fact_fle` varchar(255) DEFAULT NULL,
  `tara` varchar(255) DEFAULT NULL,
  `bruto` varchar(255) DEFAULT NULL,
  `Neto` varchar(255) DEFAULT NULL,
  `peso_cliente` varchar(255) DEFAULT NULL,
  `precio` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT '1',
  PRIMARY KEY (`id_venta`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `fact`, `factura`, `nombre`, `id_cli`, `id_direc`, `id_compra`, `id_prod`, `costo_flete`, `flete`, `fact_fle`, `tara`, `bruto`, `Neto`, `peso_cliente`, `precio`, `fecha`, `id_user`, `status`) VALUES
(1, '23652', NULL, 'SAN PABLO', '7', '18', '1', '4', '0', '123ABC', NULL, NULL, NULL, NULL, '24740', '4.50', '2025-07-25 06:00:00', '1', '1'),
(2, '11955/167000', NULL, 'BIO PAPPEL', '2', '13', '2', '11', '15005', '123ABC', NULL, NULL, NULL, NULL, '21300', '5.29', '2025-07-25 06:00:00', '1', '1');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
