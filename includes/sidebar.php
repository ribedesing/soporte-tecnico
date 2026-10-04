<?php
/**
 * Sidebar único para todos los módulos (admin, secretaria, departamento,
 * analista/técnico). Reemplaza:
 *   - admin/sidebar.php + admin/mobile-nav.php
 *   - secretaria/sidebar.php
 *   - departamento/sidebar.php
 *   - analista/sidebar.php + analista/mobile-nav.php
 *
 * Requiere que $u (usuarioActual()) ya exista. Usa las mismas variables
 * que includes/topbar.php: $moduloPath, $assetPath, $logoutPath.
 */

if (!isset($u)) {
    $u = usuarioActual();
}
$rol = $u['rol'] ?? '';
$rol = ($rol === 'tecnico') ? 'analista' : $rol;

$moduloPath = $moduloPath ?? '';
$assetPath  = $assetPath ?? '../assets/';
$logoutPath = $logoutPath ?? '../logout.php';
$paginaActual = basename($_SERVER['PHP_SELF'] ?? '');

/**
 * Config de menús por rol.
 * Cada item: [ruta, icono, texto, [páginas relacionadas que también lo activan], sección, mostrarEnBottomNav]
 */
$menus = [
    'administrador' => [
        'titulo' => 'Módulo Administrador',
        'items' => [
            ['dashboard.php',      'bi-grid-1x2',          'Mi Panel',           [], 'Principal',       true],
            ['usuarios.php',       'bi-people',             'Usuarios',           [], 'Administración',  true],
            ['departamentos.php',  'bi-building',           'Departamentos',      [], 'Administración',  false],
            ['roles.php',          'bi-shield-check',       'Roles y permisos',   [], 'Administración',  false],
            ['configuracion.php',  'bi-gear',               'Configuración',      [], 'Administración',  false],
            ['tickets.php',        'bi-ticket-detailed',    'Tickets',            [], 'Supervisión',     true],
            ['hojas.php',          'bi-file-earmark-text',  'Hojas de servicio',  [], 'Supervisión',     true],
            ['reportes.php',       'bi-bar-chart-line',     'Reportes',           [], 'Supervisión',     false],
            ['auditoria.php',      'bi-journal-text',       'Bitácora',           [], 'Auditoría',       false],
            ['seguridad.php',      'bi-lock',               'Seguridad',          [], 'Auditoría',       false],
        ],
    ],
    'secretaria' => [
        'titulo' => 'Módulo Secretaría',
        'items' => [
            ['dashboard.php',  'bi-grid-1x2',        'Panel general',      [], 'Principal', true],
            ['analistas.php',  'bi-person-badge',     'Analistas',          ['analista_detalle.php', 'analista_credenciales.php'], 'Principal', true],
            ['tickets.php',    'bi-ticket-detailed',  'Tickets',            [], 'Principal', true],
            ['hojas.php',      'bi-clipboard-data',   'Hojas de servicio',  ['ver_hoja.php'], 'Principal', true],
        ],
    ],
    'departamento' => [
        'titulo' => 'Módulo Oficina',
        'items' => [
            ['dashboard.php',     'bi-grid-1x2',         'Mi panel',           [], 'Principal', true],
            ['crear_ticket.php',  'bi-plus-circle',      'Crear ticket',       [], 'Principal', true],
            ['mis_tickets.php',   'bi-ticket-detailed',  'Mis tickets',        ['ver_ticket.php'], 'Principal', true],
            ['hojas.php',         'bi-clipboard-data',   'Hojas de servicio',  ['ver_hoja.php'], 'Principal', true],
        ],
    ],
    'analista' => [
        'titulo' => 'Módulo Analista',
        'items' => [
            ['dashboard.php',          'bi-grid-1x2',          'Mi panel',              [], 'Principal', true],
            ['tickets.php',            'bi-ticket-detailed',   'Tickets',               ['ver_ticket.php', 'tomar_ticket.php'], 'Principal', true],
            ['iniciar-servicio.php',   'bi-play-circle',       'Iniciar servicio',      ['hoja-servicio.php'], 'Principal', true],
            ['mis_hojas.php',          'bi-clipboard-data',    'Mis hojas de servicio', ['ver_hoja.php', 'continuar_servicio.php'], 'Principal', true],
        ],
    ],
];

$menu = $menus[$rol] ?? null;
if (!$menu) {
    return;
}

// Administrador: solo se muestran las secciones para las que su rol tiene permiso.
if ($rol === 'administrador') {
    $permisosMenu = [
    'usuarios.php' => 'usuarios.ver',
    'roles.php' => 'roles.ver',
    'departamentos.php' => 'departamentos.ver',
    'tickets.php' => 'tickets.ver',
    'hojas.php' => 'hojas.ver',
    'reportes.php' => 'reportes.ver',
    'auditoria.php' => 'auditoria.ver',
    'seguridad.php' => 'seguridad.ver',
    'configuracion.php' => 'configuracion.editar',
    ];
    $menu['items'] = array_values(array_filter($menu['items'], static function ($item) use ($permisosMenu) {
        return !isset($permisosMenu[$item[0]]) || tienePermiso($permisosMenu[$item[0]]);
    }));
}

if (!function_exists('_sidebarActivo')) {
    function _sidebarActivo(string $paginaActual, array $item): bool
    {
        return $paginaActual === $item[0] || in_array($paginaActual, $item[3], true);
    }
}
?>
<aside class="sidebar" id="sidebar">
  <button type="button" class="sidebar-close" aria-label="Cerrar menú"><i class="bi bi-x-lg"></i></button>
  <div class="sidebar-brand">
    <img src="<?= h($assetPath) ?>img/logo.png" alt="Logo">
    <div><div class="b-name">Soporte Técnico</div><div class="b-role"><?= h($menu['titulo']) ?></div></div>
  </div>
  <nav class="sidebar-nav">
    <?php $seccionAnterior = null; ?>
    <?php foreach ($menu['items'] as $item): ?>
      <?php if ($item[4] !== $seccionAnterior): $seccionAnterior = $item[4]; ?>
        <div class="nav-label mt-3"><?= h($item[4]) ?></div>
      <?php endif; ?>
      <a class="nav-link <?= _sidebarActivo($paginaActual, $item) ? 'active' : '' ?>" href="<?= h($moduloPath . $item[0]) ?>">
        <i class="bi <?= h($item[1]) ?>"></i> <?= h($item[2]) ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">
    <form method="post" action="<?= h($logoutPath) ?>">
      <?= csrf_field() ?>
      <button type="submit" class="logout"><i class="bi bi-box-arrow-left"></i> Cerrar sesión</button>
    </form>
  </div>
</aside>

<?php $itemsBottom = array_values(array_filter($menu['items'], fn($i) => $i[5])); ?>
<?php $itemsMas = array_values(array_filter($menu['items'], fn($i) => !$i[5])); ?>

<?php if ($itemsMas): ?>
<nav class="admin-mobile-nav no-print" aria-label="Navegación móvil">
  <?php foreach ($itemsBottom as $item): ?>
    <a href="<?= h($moduloPath . $item[0]) ?>"><i class="bi <?= h($item[1]) ?>"></i><span><?= h($item[2]) ?></span></a>
  <?php endforeach; ?>
  <button type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMasMenu" aria-controls="sidebarMasMenu"><i class="bi bi-three-dots"></i><span>Más</span></button>
</nav>
<div class="offcanvas offcanvas-end admin-more-menu no-print" tabindex="-1" id="sidebarMasMenu" aria-labelledby="sidebarMasMenuLabel">
  <div class="offcanvas-header"><h2 class="offcanvas-title" id="sidebarMasMenuLabel">Más opciones</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button></div>
  <div class="offcanvas-body p-2"><nav class="admin-more-links" aria-label="Más opciones del menú">
    <?php foreach ($itemsMas as $item): ?>
      <a href="<?= h($moduloPath . $item[0]) ?>"><i class="bi <?= h($item[1]) ?>"></i><span><?= h($item[2]) ?></span></a>
    <?php endforeach; ?>
  </nav></div>
</div>
<?php else: ?>
<nav class="bottom-nav no-print" aria-label="Navegación móvil">
  <div class="bn-grid">
    <?php foreach ($itemsBottom as $item): ?>
      <a href="<?= h($moduloPath . $item[0]) ?>" class="bn-item <?= _sidebarActivo($paginaActual, $item) ? 'active' : '' ?>">
        <i class="bi <?= h($item[1]) ?>"></i><span><?= h($item[2]) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>
