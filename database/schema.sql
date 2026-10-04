-- ============================================================
-- Sistema de Soporte Técnico - Esquema de base de datos
-- MySQL 8 / MariaDB 10.4+ (utf8mb4)
--
-- Contiene la estructura de todas las tablas y los catálogos base
-- (roles, permisos, tipos de servicio y configuración inicial).
-- NO contiene usuarios, departamentos ni datos de ninguna persona.
--
-- Uso:
--   CREATE DATABASE soporte_tecnico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   mysql -u USUARIO -p soporte_tecnico < database/schema.sql
--
-- Para una demostración con datos ficticios, importar después:
--   database/seed_demo.sql
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: soporte_tecnico
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_accesos`
--

CREATE TABLE `auditoria_accesos` (
  `id_auditoria` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auth_rate_limits`
--

CREATE TABLE `auth_rate_limits` (
  `scope` varchar(32) NOT NULL,
  `key_hash` char(64) NOT NULL,
  `bucket_start` int(10) UNSIGNED NOT NULL,
  `hits` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cambios_correo_pendientes`
--

CREATE TABLE `cambios_correo_pendientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `correo_nuevo` varchar(190) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_en` datetime NOT NULL,
  `usado_en` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `clave` varchar(80) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`clave`, `valor`, `descripcion`, `updated_at`) VALUES
('correo_institucional', '', 'Correo institucional', CURRENT_TIMESTAMP),
('direccion_institucional', '', 'Dirección institucional', CURRENT_TIMESTAMP),
('institucion', 'Mi Organización', 'Institución', CURRENT_TIMESTAMP),
('nombre_sistema', 'Soporte Técnico', 'Nombre visible del sistema', CURRENT_TIMESTAMP),
('telefono_institucional', '', 'Teléfono institucional', CURRENT_TIMESTAMP),
('timezone', 'America/Caracas', 'Zona horaria', CURRENT_TIMESTAMP);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `confirmaciones_correo`
--

CREATE TABLE `confirmaciones_correo` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_en` datetime NOT NULL,
  `usado_en` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `csrf_tokens`
--

CREATE TABLE `csrf_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `token` char(64) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id_departamentos` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hojas_servicio`
--

CREATE TABLE `hojas_servicio` (
  `id_hojas` int(11) NOT NULL,
  `tecnico_id` int(11) NOT NULL,
  `departamento_solicitante_id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time DEFAULT NULL,
  `otro_especifique` varchar(255) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `estatus` enum('completado','en_proceso','pendiente_insumos') NOT NULL DEFAULT 'en_proceso',
  `usuario_atendido_nombre` varchar(100) NOT NULL,
  `firma_imagen` varchar(255) DEFAULT NULL,
  `documento_adjunto` varchar(255) DEFAULT NULL,
  `tiempo_total_minutos` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hoja_servicio_evidencias`
--

CREATE TABLE `hoja_servicio_evidencias` (
  `id` int(11) NOT NULL,
  `hoja_id` int(11) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hoja_servicio_tipo`
--

CREATE TABLE `hoja_servicio_tipo` (
  `hoja_id` int(11) NOT NULL,
  `tipo_servicio_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_intentos`
--

CREATE TABLE `login_intentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `fallidos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expira_en` datetime NOT NULL,
  `usado_en` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles_usuarios`
--

CREATE TABLE `perfiles_usuarios` (
  `id_perfil` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `confirmar_correo` tinyint(1) DEFAULT 0,
  `telefono` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `departamento_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `clave` varchar(80) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `clave`, `modulo`, `descripcion`) VALUES
(1, 'usuarios.ver', 'Administración', 'Consultar usuarios'),
(2, 'usuarios.crear', 'Administración', 'Crear usuarios'),
(3, 'usuarios.estado', 'Administración', 'Activar o desactivar cuentas'),
(4, 'roles.ver', 'Administración', 'Consultar roles y permisos'),
(5, 'roles.editar', 'Administración', 'Modificar permisos de roles'),
(6, 'departamentos.ver', 'Administración', 'Consultar departamentos'),
(7, 'departamentos.gestionar', 'Administración', 'Crear y activar/desactivar departamentos'),
(8, 'tickets.ver', 'Supervisión', 'Consultar todos los tickets'),
(9, 'hojas.ver', 'Supervisión', 'Consultar hojas de servicio'),
(10, 'reportes.ver', 'Supervisión', 'Consultar reportes'),
(11, 'auditoria.ver', 'Auditoría', 'Consultar bitácora'),
(12, 'seguridad.ver', 'Auditoría', 'Consultar actividad y seguridad'),
(13, 'configuracion.editar', 'Administración', 'Modificar configuración institucional');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_roles` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_roles`, `nombre`, `descripcion`, `created_at`) VALUES
(1, 'administrador', 'Acceso total al sistema', CURRENT_TIMESTAMP),
(2, 'secretaria', 'Gestión administrativa y documental', CURRENT_TIMESTAMP),
(3, 'analista', 'Seguimiento y reportes técnicos', CURRENT_TIMESTAMP),
(4, 'departamento', 'Personal de oficinas que solicita soporte técnico', CURRENT_TIMESTAMP);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `rol_id` int(11) NOT NULL,
  `permiso_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol_permisos`
--

INSERT INTO `rol_permisos` (`rol_id`, `permiso_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id_tickets` int(11) NOT NULL,
  `solicitante_id` int(11) NOT NULL,
  `departamento_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `estado` enum('pendiente','asignado','en_proceso','completado','cancelado') NOT NULL DEFAULT 'pendiente',
  `analista_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_asignacion` timestamp NULL DEFAULT NULL,
  `fecha_inicio` timestamp NULL DEFAULT NULL,
  `fecha_cierre` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_servicio`
--

CREATE TABLE `tipos_servicio` (
  `id_servicios` int(11) NOT NULL,
  `codigo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_servicio`
--

INSERT INTO `tipos_servicio` (`id_servicios`, `codigo`, `nombre`, `estado`, `created_at`) VALUES
(1, 1, 'Reparación de equipo', 'activo', CURRENT_TIMESTAMP),
(2, 2, 'Instalación de software', 'activo', CURRENT_TIMESTAMP),
(3, 3, 'Configuración de red', 'activo', CURRENT_TIMESTAMP),
(4, 4, 'Mantenimiento preventivo', 'activo', CURRENT_TIMESTAMP),
(5, 5, 'Capacitación', 'activo', CURRENT_TIMESTAMP),
(6, 6, 'Asesoría técnica', 'activo', CURRENT_TIMESTAMP),
(7, 7, 'Soporte remoto', 'activo', CURRENT_TIMESTAMP),
(8, 8, 'Revisión de seguridad', 'activo', CURRENT_TIMESTAMP),
(9, 9, 'Actualización de sistemas', 'activo', CURRENT_TIMESTAMP),
(10, 10, 'Otro (especifique)', 'activo', CURRENT_TIMESTAMP);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuarios` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `version_sesion` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ultima_actividad` timestamp NULL DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `rol_id` int(11) NOT NULL DEFAULT 4,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditoria_accesos`
--
ALTER TABLE `auditoria_accesos`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `idx_usuario_accion` (`usuario_id`,`accion`);

--
-- Indices de la tabla `auth_rate_limits`
--
ALTER TABLE `auth_rate_limits`
  ADD PRIMARY KEY (`scope`,`key_hash`,`bucket_start`),
  ADD KEY `idx_auth_rate_bucket` (`bucket_start`);

--
-- Indices de la tabla `cambios_correo_pendientes`
--
ALTER TABLE `cambios_correo_pendientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cambio_correo_token` (`token_hash`),
  ADD KEY `idx_cambio_correo_usuario` (`usuario_id`),
  ADD KEY `idx_cambio_correo_expira` (`expira_en`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `confirmaciones_correo`
--
ALTER TABLE `confirmaciones_correo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `token_hash` (`token_hash`);

--
-- Indices de la tabla `csrf_tokens`
--
ALTER TABLE `csrf_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_csrf_tokens_token` (`token`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id_departamentos`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `hojas_servicio`
--
ALTER TABLE `hojas_servicio`
  ADD PRIMARY KEY (`id_hojas`),
  ADD KEY `departamento_solicitante_id` (`departamento_solicitante_id`),
  ADD KEY `idx_tecnico_fecha` (`tecnico_id`,`fecha`),
  ADD KEY `idx_estatus` (`estatus`),
  ADD KEY `idx_hoja_ticket` (`ticket_id`);

--
-- Indices de la tabla `hoja_servicio_evidencias`
--
ALTER TABLE `hoja_servicio_evidencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hoja_id` (`hoja_id`);

--
-- Indices de la tabla `hoja_servicio_tipo`
--
ALTER TABLE `hoja_servicio_tipo`
  ADD PRIMARY KEY (`hoja_id`,`tipo_servicio_id`),
  ADD KEY `tipo_servicio_id` (`tipo_servicio_id`);

--
-- Indices de la tabla `login_intentos`
--
ALTER TABLE `login_intentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_login_intentos_usuario` (`usuario`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`,`leida`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_correo` (`correo`),
  ADD KEY `idx_token_hash` (`token_hash`);

--
-- Indices de la tabla `perfiles_usuarios`
--
ALTER TABLE `perfiles_usuarios`
  ADD PRIMARY KEY (`id_perfil`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `idx_perfil_departamento` (`departamento_id`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_permiso_clave` (`clave`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_roles`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD PRIMARY KEY (`rol_id`,`permiso_id`),
  ADD KEY `fk_rp_permiso` (`permiso_id`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id_tickets`),
  ADD KEY `idx_ticket_solicitante` (`solicitante_id`),
  ADD KEY `idx_ticket_departamento` (`departamento_id`),
  ADD KEY `idx_ticket_analista` (`analista_id`),
  ADD KEY `idx_ticket_estado` (`estado`);

--
-- Indices de la tabla `tipos_servicio`
--
ALTER TABLE `tipos_servicio`
  ADD PRIMARY KEY (`id_servicios`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuarios`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `idx_usuario` (`usuario`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria_accesos`
--
ALTER TABLE `auditoria_accesos`
  MODIFY `id_auditoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cambios_correo_pendientes`
--
ALTER TABLE `cambios_correo_pendientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `confirmaciones_correo`
--
ALTER TABLE `confirmaciones_correo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `csrf_tokens`
--
ALTER TABLE `csrf_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id_departamentos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `hojas_servicio`
--
ALTER TABLE `hojas_servicio`
  MODIFY `id_hojas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `hoja_servicio_evidencias`
--
ALTER TABLE `hoja_servicio_evidencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `login_intentos`
--
ALTER TABLE `login_intentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `perfiles_usuarios`
--
ALTER TABLE `perfiles_usuarios`
  MODIFY `id_perfil` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_roles` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id_tickets` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_servicio`
--
ALTER TABLE `tipos_servicio`
  MODIFY `id_servicios` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuarios` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria_accesos`
--
ALTER TABLE `auditoria_accesos`
  ADD CONSTRAINT `auditoria_accesos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuarios`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cambios_correo_pendientes`
--
ALTER TABLE `cambios_correo_pendientes`
  ADD CONSTRAINT `fk_cambio_correo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuarios`) ON DELETE CASCADE;

--
-- Filtros para la tabla `confirmaciones_correo`
--
ALTER TABLE `confirmaciones_correo`
  ADD CONSTRAINT `confirmaciones_correo_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuarios`) ON DELETE CASCADE;

--
-- Filtros para la tabla `hojas_servicio`
--
ALTER TABLE `hojas_servicio`
  ADD CONSTRAINT `fk_hoja_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id_tickets`) ON DELETE SET NULL,
  ADD CONSTRAINT `hojas_servicio_ibfk_1` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id_usuarios`),
  ADD CONSTRAINT `hojas_servicio_ibfk_2` FOREIGN KEY (`departamento_solicitante_id`) REFERENCES `departamentos` (`id_departamentos`);

--
-- Filtros para la tabla `hoja_servicio_evidencias`
--
ALTER TABLE `hoja_servicio_evidencias`
  ADD CONSTRAINT `fk_evidencia_hoja` FOREIGN KEY (`hoja_id`) REFERENCES `hojas_servicio` (`id_hojas`) ON DELETE CASCADE;

--
-- Filtros para la tabla `hoja_servicio_tipo`
--
ALTER TABLE `hoja_servicio_tipo`
  ADD CONSTRAINT `hoja_servicio_tipo_ibfk_1` FOREIGN KEY (`hoja_id`) REFERENCES `hojas_servicio` (`id_hojas`) ON DELETE CASCADE,
  ADD CONSTRAINT `hoja_servicio_tipo_ibfk_2` FOREIGN KEY (`tipo_servicio_id`) REFERENCES `tipos_servicio` (`id_servicios`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuarios`) ON DELETE CASCADE;

--
-- Filtros para la tabla `perfiles_usuarios`
--
ALTER TABLE `perfiles_usuarios`
  ADD CONSTRAINT `fk_perfil_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id_departamentos`),
  ADD CONSTRAINT `perfiles_usuarios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuarios`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id_roles`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_ticket_analista` FOREIGN KEY (`analista_id`) REFERENCES `usuarios` (`id_usuarios`),
  ADD CONSTRAINT `fk_ticket_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id_departamentos`),
  ADD CONSTRAINT `fk_ticket_solicitante` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id_usuarios`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id_roles`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
