<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requerirRol(['administrador']);
$u = usuarioActual();

function adminLayoutStart(string $title): void {
    global $u, $pdo;
    $rootPath = '../';
    $assetPath = '../assets/';
    $moduloPath = '';
    $logoutPath = '../logout.php';
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?= h($title) ?> | Soporte Técnico</title>
      <link rel="icon" href="../assets/img/logo.png">
      <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
      <link href="../assets/css/style.css?v=20261001-blue" rel="stylesheet">
      <link href="../assets/css/admin.css?v=20261001-blue" rel="stylesheet">
    </head>
    <body>
    <div class="app-shell">
      <?php include __DIR__ . '/../includes/sidebar.php'; ?>
      <div class="main">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <div class="content admin-content">
    <?php
}
function adminLayoutEnd(): void {
    ?>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
    (() => {
      const sidebar=document.getElementById('sidebar');
      const toggle=document.getElementById('sidebarToggle');
      if(sidebar && toggle) toggle.addEventListener('click',()=>sidebar.classList.toggle('show'));
      const close=sidebar?.querySelector('.sidebar-close');
      if(close) close.addEventListener('click',()=>sidebar.classList.remove('show'));
      let ov=document.getElementById('sidebarOverlay');
      if(!ov && sidebar){ov=document.createElement('div');ov.id='sidebarOverlay';ov.className='sidebar-overlay';sidebar.after(ov);}
      if(ov) ov.addEventListener('click',()=>sidebar?.classList.remove('show'));
    })();
    </script>
    <script src="../assets/js/csp-handlers.js" defer></script></body></html>
    <?php
}
