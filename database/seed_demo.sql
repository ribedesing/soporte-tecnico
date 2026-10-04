-- ============================================================
-- Sistema de Soporte Técnico - Datos de DEMOSTRACIÓN (100% ficticios)
--
-- ADVERTENCIA: este script es solo para una base de datos NUEVA y
-- dedicada a la demo. No lo ejecutes sobre una base con datos reales.
--
-- Requisito: haber importado antes database/schema.sql
--   mysql -u USUARIO -p soporte_tecnico < database/seed_demo.sql
--
-- Cuentas demo (las contraseñas son públicas; no las reutilices en
-- ningún otro sitio):
--   admin.demo       / Demo-Admin-2026!        (administrador)
--   secretaria.demo  / Demo-Secretaria-2026!   (secretaría)
--   analista.demo    / Demo-Analista-2026!     (analista)
--   oficina.demo     / Demo-Oficina-2026!      (departamento/oficina)
-- ============================================================

START TRANSACTION;

-- Departamentos ficticios -------------------------------------
INSERT INTO `departamentos` (`id_departamentos`, `nombre`, `estado`) VALUES
(1, 'Recursos Humanos', 'activo'),
(2, 'Finanzas', 'activo'),
(3, 'Obras Públicas', 'activo'),
(4, 'Desarrollo Social', 'activo'),
(5, 'Administración', 'activo'),
(6, 'Archivo y Registro', 'activo');

-- Usuarios (rol_id: 1 administrador, 2 secretaria, 3 analista, 4 departamento)
INSERT INTO `usuarios` (`id_usuarios`, `usuario`, `contrasena`, `rol_id`, `estado`) VALUES
(1, 'admin.demo',      '$2y$12$b7Nb.LjPeae5Be4OXcGFhezgkLcaHz/dTmEIdvXkzufUxccjUHYVW',      1, 'activo'),
(2, 'secretaria.demo', '$2y$12$Ek8W0BvtRqcApx6EUf/bEuhGLJhoh32Yg4sZ2lh2rlqS7c.7IHS4G', 2, 'activo'),
(3, 'analista.demo',   '$2y$12$1O8ERNLKuRQPuSxMygvvheNMdn5cTU/XfDi792Z/pl0/.FWw72L4G',   3, 'activo'),
(4, 'oficina.demo',    '$2y$12$s19xRX1V12YuY.NZRmEds.6J52BdAkhmdFrLyEFeT8RyzyL4S2e/K',      4, 'activo');

-- Perfiles ficticios (correos en example.com, cédulas inventadas)
INSERT INTO `perfiles_usuarios`
  (`id_perfil`, `usuario_id`, `nombre`, `apellido`, `cedula`, `correo`, `confirmar_correo`, `telefono`, `departamento_id`) VALUES
(1, 1, 'Admin',      'Demo', 'V-90000001', 'admin.demo@example.com',      1, NULL, 5),
(2, 2, 'Secretaria', 'Demo', 'V-90000002', 'secretaria.demo@example.com', 1, NULL, 5),
(3, 3, 'Analista',   'Demo', 'V-90000003', 'analista.demo@example.com',   1, NULL, 5),
(4, 4, 'Oficina',    'Demo', 'V-90000004', 'oficina.demo@example.com',    1, NULL, 2);

-- Tickets de ejemplo (estados: pendiente, asignado, completado) -
INSERT INTO `tickets`
  (`id_tickets`, `solicitante_id`, `departamento_id`, `titulo`, `descripcion`, `estado`, `analista_id`,
   `fecha_creacion`, `fecha_asignacion`, `fecha_inicio`, `fecha_cierre`) VALUES
(1, 4, 2, 'La impresora del área no imprime',
 'La impresora compartida muestra error de papel atascado, pero no hay papel dentro.',
 'pendiente', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, NULL, NULL),
(2, 4, 2, 'Sin conexión de red en la oficina',
 'Dos equipos del área perdieron acceso a la red local desde esta mañana.',
 'pendiente', NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR), NULL, NULL, NULL),
(3, 4, 2, 'Instalar software de contabilidad',
 'Se necesita instalar la versión actualizada del programa de contabilidad en un equipo nuevo.',
 'asignado', 3, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, NULL),
(4, 4, 2, 'Computadora lenta y se reinicia sola',
 'El equipo tarda varios minutos en arrancar y se reinicia durante el día.',
 'completado', 3, DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY),
 DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Hoja de servicio de ejemplo para el ticket completado (sin firma ni fotos)
INSERT INTO `hojas_servicio`
  (`id_hojas`, `tecnico_id`, `departamento_solicitante_id`, `ticket_id`, `fecha`, `hora_inicio`, `hora_fin`,
   `descripcion`, `estatus`, `usuario_atendido_nombre`, `tiempo_total_minutos`) VALUES
(1, 3, 2, 4, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '09:00:00', '10:15:00',
 'Se realizó limpieza de archivos temporales, se actualizó el sistema operativo y se reemplazó la pasta térmica. El equipo quedó estable.',
 'completado', 'Persona de Ejemplo', 75);

-- Tipos de servicio aplicados: 1 Reparación de equipo, 4 Mantenimiento preventivo
INSERT INTO `hoja_servicio_tipo` (`hoja_id`, `tipo_servicio_id`) VALUES
(1, 1),
(1, 4);

COMMIT;
