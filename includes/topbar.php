<?php
require_once __DIR__ . '/notificaciones.php';
if (!isset($u)) $u = usuarioActual();

$autoRootPath = rutaBaseAplicacion();

$rootPath = rtrim($rootPath ?? $autoRootPath, '/') . '/';
$assetPath = rtrim($assetPath ?? ($autoRootPath . 'assets/'), '/') . '/';
$logoutPath = $logoutPath ?? ($rootPath . 'logout.php');

if (!isset($moduloPath)) {
    $moduloPath = $autoRootPath;
}

$perfilTop = [];
try {
    $q = $pdo->prepare('SELECT nombre,apellido,avatar,correo,confirmar_correo FROM perfiles_usuarios WHERE usuario_id=? LIMIT 1');
    $q->execute([$u['id']]);
    $perfilTop = $q->fetch() ?: [];
} catch (Throwable $e) {}

$nombreTop = trim(($perfilTop['nombre'] ?? '') . ' ' . ($perfilTop['apellido'] ?? ''))
    ?: ($u['nombre_completo'] ?: $u['usuario']);
$avatarTop = $perfilTop['avatar'] ?? '';

$noLeidas = 0;
$notifs = [];
try {
  if (($u['rol'] ?? '') === 'secretaria') {
    generarNotificacionesSecretaria($pdo);
  }
  if (($u['rol'] ?? '') === 'administrador') {
    generarNotificacionesAdministrador($pdo);
  }
    $q = $pdo->prepare('SELECT id,titulo,mensaje,url,leida,created_at FROM notificaciones WHERE usuario_id=? ORDER BY created_at DESC LIMIT 8');
    $q->execute([$u['id']]);
    $notifs = $q->fetchAll();
    foreach ($notifs as $n) if (!(int)$n['leida']) $noLeidas++;
} catch (Throwable $e) {}

/* Perfil unificado: todos los módulos comparten includes/perfil.php */
$perfilUrl = $rootPath . 'includes/perfil.php';

?>

<header class="topbar">
  <div class="d-flex align-items-center gap-2">
    <div>
      <h1>Mi Panel</h1>
      <div class="subtitle"><?= date('d/m/Y') ?></div>
    </div>
  </div>

  <div class="d-flex align-items-center gap-2">

    <!-- En escritorio se muestran como pestaña; en móvil se abre la vista completa. -->
    <div class="dropdown topbar-notifications-desktop">
      <button class="btn btn-light border position-relative topbar-notifications" data-bs-toggle="dropdown" aria-label="Ver notificaciones" title="Notificaciones">
        <i class="bi bi-bell"></i>
        <?php if ($noLeidas): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger"><?= $noLeidas ?></span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu dropdown-menu-end shadow notification-dropdown">
        <div class="px-3 py-2 fw-semibold d-flex justify-content-between align-items-center">
          <span>Notificaciones</span>
          <?php if ($noLeidas): ?>
            <form method="post" action="<?= $rootPath ?>includes/notificaciones.php?accion=marcar" class="mb-0">
              <?= csrf_field() ?>
              <input type="hidden" name="volver" value="<?= h($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
              <button type="submit" class="btn btn-link btn-sm p-0" title="Marcar todas como leídas">Marcar todas</button>
            </form>
          <?php endif; ?>
        </div>
        <?php if (!$notifs): ?>
          <div class="px-3 py-3 text-muted small">No tienes notificaciones.</div>
        <?php endif; ?>
        <?php foreach ($notifs as $n): ?>
          <div class="dropdown-item py-2 <?= !(int)$n['leida'] ? 'bg-light' : '' ?> d-flex gap-2 align-items-start">
            <form method="post" action="<?= $rootPath ?>includes/notificaciones.php?accion=abrir" class="flex-grow-1 mb-0">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
              <button type="submit" class="btn btn-link text-reset text-decoration-none text-start p-0 w-100">
                <div class="fw-semibold small"><?= h($n['titulo']) ?></div>
                <div class="small text-muted"><?= h($n['mensaje']) ?></div>
              </button>
            </form>
            <?php if (!(int)$n['leida']): ?>
              <form method="post" action="<?= $rootPath ?>includes/notificaciones.php?accion=marcar" class="mb-0">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                <input type="hidden" name="volver" value="<?= h($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
                <button type="submit" class="btn btn-sm btn-outline-brand py-0 px-1" title="Marcar como leída" aria-label="Marcar como leída"><i class="bi bi-check2"></i></button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <div class="border-top px-3 py-2 text-end">
          <a class="small text-decoration-none" href="<?= $rootPath ?>includes/notificaciones.php">Ver todas las notificaciones</a>
        </div>
      </div>
    </div>

    <a class="btn btn-light border position-relative topbar-notifications-mobile" href="<?= $rootPath ?>includes/notificaciones.php" aria-label="Ver notificaciones" title="Notificaciones">
      <i class="bi bi-bell"></i>
      <?php if ($noLeidas): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger"><?= $noLeidas ?></span>
      <?php endif; ?>
    </a>

    <!-- Usuario -->
    <div class="dropdown">
      <button class="btn p-0 border-0 bg-transparent d-flex align-items-center gap-2" data-bs-toggle="dropdown">
        <div class="avatar overflow-hidden">
          <?php if ($avatarTop): ?>
            <img src="<?= h($rootPath) ?>includes/archivo_privado.php?tipo=avatar&amp;id=<?= (int)$u['id'] ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <?= h(iniciales($nombreTop)) ?>
          <?php endif; ?>
        </div>
        <div class="d-none d-md-block text-start">
          <div style="font-weight:600;font-size:.88rem"><?= h($nombreTop) ?></div>
          <div class="text-muted" style="font-size:.72rem"><?= h(ucfirst($u['rol'])) ?></div>
        </div>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
          <a class="dropdown-item" href="<?= $perfilUrl ?>">
            <i class="bi bi-person-gear me-2"></i>Administrar mi perfil
          </a>
        </li>
        <li>
          <button type="button" class="dropdown-item d-flex align-items-center justify-content-between" id="themeToggle">
            <span><i class="bi bi-moon-stars me-2" id="themeToggleIcon"></i>Modo oscuro</span>
            <span class="theme-switch-indicator" aria-hidden="true"></span>
          </button>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= h(rtrim($rootPath, '/') . '/legal/privacidad.php') ?>" target="_blank" rel="noopener"><i class="bi bi-shield-lock me-2"></i>Privacidad y términos</a></li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <form method="post" action="<?= h($logoutPath) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="dropdown-item text-danger">
              <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
            </button>
          </form>
        </li>
      </ul>
    </div>

  </div>
</header>

<div id="notificacionToastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1090"></div>

<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
(function () {
  const key = 'soporte-theme';
  const toggle = document.getElementById('themeToggle');
  const icon = document.getElementById('themeToggleIcon');
  const label = toggle ? toggle.querySelector('span') : null;

  function aplicarTema(tema) {
    const oscuro = tema === 'dark';
    document.documentElement.classList.toggle('dark-mode', oscuro);
    document.body.classList.toggle('dark-mode', oscuro);
    if (icon) {
      icon.classList.toggle('bi-moon-stars', !oscuro);
      icon.classList.toggle('bi-sun', oscuro);
    }
    if (label) label.lastChild.textContent = oscuro ? 'Modo claro' : 'Modo oscuro';
  }

  aplicarTema(localStorage.getItem(key) || 'light');
  if (toggle) {
    toggle.addEventListener('click', function () {
      const nuevoTema = document.documentElement.classList.contains('dark-mode') ? 'light' : 'dark';
      localStorage.setItem(key, nuevoTema);
      aplicarTema(nuevoTema);
    });
  }
})();

(function () {
    const vistos = new Set();
    let primeraConsulta = true;
  const rutaRaiz = <?= json_encode($rootPath, JSON_UNESCAPED_SLASHES) ?>;
  let csrfTok = <?= json_encode(csrf_token()) ?>;

    async function revisarNotificaciones() {
        try {
            const r = await fetch('<?= $rootPath ?>includes/notificaciones.php?accion=pendientes', { cache: 'no-store' });
            if (!r.ok) return;
            const data = await r.json();
            if (!data.ok) return;

            (data.notificaciones || []).forEach(n => {
                const id = String(n.id);
                if (!vistos.has(id)) {
                    vistos.add(id);
                    if (!primeraConsulta) mostrarNotificacion(n);
                }
            });
            primeraConsulta = false;
        } catch (e) {}
    }

    function mostrarNotificacion(n) {
        const cont = document.getElementById('notificacionToastContainer');
        if (!cont) return;
      reproducirSonidoNotificacion();
        const toast = document.createElement('div');
        toast.className = 'toast show shadow mb-2';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="toast-header">
                <i class="bi bi-bell-fill me-2"></i>
                <strong class="me-auto">${escapeHtml(n.titulo)}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                <div class="mb-2">${escapeHtml(n.mensaje)}</div>
              <div class="d-flex gap-2 flex-wrap">
                ${n.url ? `<a class="btn btn-sm btn-brand" href="${escapeAttr(rutaRaiz + String(n.url).replace(/^\.\//, ''))}">Ver detalle</a>` : ''}
                <a class="btn btn-sm btn-outline-brand" href="${escapeAttr(rutaRaiz + 'includes/notificaciones.php')}">Ver todos</a>
                <button type="button" class="btn btn-sm btn-light border toast-marcar-leida">Marcar como leída</button>
              </div>
            </div>`;
        cont.appendChild(toast);
          const marcar = toast.querySelector('.toast-marcar-leida');
          if (marcar) marcar.addEventListener('click', async () => {
            marcar.disabled = true;
            try {
              const tr = await fetch(rutaRaiz + 'includes/csrf_token.php', {cache: 'no-store'});
              if (!tr.ok) throw new Error('csrf');
              const td = await tr.json();
              csrfTok = td.csrf_token || csrfTok;
              await fetch(rutaRaiz + 'includes/notificaciones.php?accion=marcar', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(n.id) + '&csrf_token=' + encodeURIComponent(csrfTok)
              });
              toast.remove();
            } catch (e) { marcar.disabled = false; }
          });
        setTimeout(() => toast.remove(), 10000);

        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(n.titulo, { body: n.mensaje });
        }
    }

      function reproducirSonidoNotificacion() {
        try {
          const AudioContext = window.AudioContext || window.webkitAudioContext;
          if (!AudioContext) return;
          const contexto = window.__soporteAudio || (window.__soporteAudio = new AudioContext());
          if (contexto.state === 'suspended') contexto.resume();
          const ahora = contexto.currentTime;
          const oscilador = contexto.createOscillator();
          const ganancia = contexto.createGain();
          oscilador.type = 'sine';
          oscilador.frequency.setValueAtTime(740, ahora);
          oscilador.frequency.exponentialRampToValueAtTime(988, ahora + .12);
          ganancia.gain.setValueAtTime(.0001, ahora);
          ganancia.gain.exponentialRampToValueAtTime(.100, ahora + .015);
          ganancia.gain.exponentialRampToValueAtTime(.0001, ahora + .22);
          oscilador.connect(ganancia);
          ganancia.connect(contexto.destination);
          oscilador.start(ahora);
          oscilador.stop(ahora + .23);
        } catch (e) {}
      }

    function escapeHtml(s) {
        const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML;
    }
    function escapeAttr(s) { return String(s || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    revisarNotificaciones();
    setInterval(revisarNotificaciones, 5000);

    async function enviarActividad() {
      try {
        const r = await fetch('<?= $rootPath ?>includes/csrf_token.php', {cache: 'no-store'});
        if (!r.ok) return;
        const tokenData = await r.json();
        csrfTok = tokenData.csrf_token || csrfTok;
        await fetch('<?= $rootPath ?>includes/actividad.php', { method: 'POST', cache: 'no-store', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'csrf_token=' + encodeURIComponent(csrfTok) });
      } catch (e) {}
    }

    enviarActividad();
    setInterval(enviarActividad, 60000);

    async function actualizarEstadosAnalistas() {
      const tabla = document.getElementById('tablaAnalistas');
      if (!tabla) return;
      try {
        const r = await fetch('<?= $rootPath ?>includes/analistas_estado.php', { cache: 'no-store' });
        if (!r.ok) return;
        const data = await r.json();
        if (!data.ok) return;
        (data.analistas || []).forEach(a => {
          const fila = tabla.querySelector(`[data-analista-id="${a.id_usuarios}"]`);
          const estado = fila && fila.querySelector('.estado-analista');
          if (!estado) return;
          const activo = Boolean(Number(a.en_linea));
          estado.classList.toggle('badge-completado', activo);
          estado.classList.toggle('badge-pendiente', !activo);
          estado.innerHTML = `<i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>${activo ? 'Activo' : 'Inactivo'}`;
        });
      } catch (e) {}
    }

    actualizarEstadosAnalistas();
    setInterval(actualizarEstadosAnalistas, 15000);
})();
</script>
