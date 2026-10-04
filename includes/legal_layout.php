<?php
/**
 * Layout compartido de las páginas legales (privacidad, términos, cookies).
 * Son páginas PÚBLICAS: no requieren sesión, para poder enlazarlas desde
 * el login y el registro.
 */
require_once __DIR__ . '/helpers.php';

const LEGAL_CONTACTO = 'soporte@ejemplo.com';
const LEGAL_ACTUALIZADO = '2026-09-28';

function legalLayoutStart(string $titulo, string $activa): void
{
    $paginas = [
        'privacidad' => ['privacidad.php', 'bi-shield-lock', 'Privacidad'],
        'terminos'   => ['terminos.php',   'bi-file-earmark-text', 'Términos de uso'],
        'cookies'    => ['cookies.php',    'bi-cookie', 'Cookies'],
    ];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($titulo) ?> | Soporte Técnico</title>
<link rel="icon" href="../assets/img/logo.png">
<meta name="theme-color" content="#0A3F3A">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
  html,body{height:auto;}
  .legal-top{background:var(--teal-900);color:#fff;padding:1rem 0;}
  .legal-top img{height:38px;width:auto;filter:brightness(0) invert(1);}
  .legal-top a{color:#BFE3DC;font-size:.85rem;}
  .legal-wrap{max-width:860px;margin:0 auto;padding:1.5rem 1rem 3rem;}
  .legal-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;}
  .legal-tabs a{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;border:1px solid var(--border);
    border-radius:999px;background:var(--card);color:var(--ink-soft);font-size:.88rem;font-weight:500;}
  .legal-tabs a.active{background:var(--teal-700);border-color:var(--teal-700);color:#fff;}
  .legal-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
    box-shadow:var(--shadow);padding:2rem 2rem 1.5rem;}
  .legal-card h1{font-size:1.6rem;font-weight:700;margin-bottom:.25rem;}
  .legal-card h2{font-size:1.1rem;font-weight:700;margin:1.75rem 0 .5rem;color:var(--teal-800);}
  .legal-card p,.legal-card li{font-size:.93rem;line-height:1.65;color:var(--ink);}
  .legal-card ul{padding-left:1.2rem;}
  .legal-meta{font-size:.8rem;color:var(--ink-soft);margin-bottom:1rem;}
  .legal-card table{font-size:.86rem;}
  .legal-aviso{background:var(--amber-bg);border-radius:10px;padding:.75rem 1rem;font-size:.85rem;margin:1rem 0;}
  .legal-foot{text-align:center;font-size:.75rem;color:var(--ink-soft);margin-top:1.5rem;}
  .dark-mode .legal-card,.dark-mode .legal-tabs a{background:var(--card);}
  .dark-mode .legal-card h2{color:var(--teal-600);}
  .dark-mode .legal-aviso{background:rgba(232,163,61,.15);}
  @media (max-width:576px){.legal-card{padding:1.25rem 1rem;}}
  @media print{.legal-top,.legal-tabs,.legal-foot .no-print{display:none;}.legal-card{border:0;box-shadow:none;padding:0;}}
</style>
</head>
<body>
<header class="legal-top">
  <div class="legal-wrap py-0 d-flex align-items-center justify-content-between">
    <img src="../assets/img/logo.png" alt="Logo del sistema">
    <a href="../index.php"><i class="bi bi-arrow-left"></i> Volver al sistema</a>
  </div>
</header>
<main class="legal-wrap">
  <nav class="legal-tabs" aria-label="Documentos legales">
    <?php foreach ($paginas as $clave => [$url, $icono, $etiqueta]): ?>
      <a href="<?= h($url) ?>" class="<?= $clave === $activa ? 'active' : '' ?>"<?= $clave === $activa ? ' aria-current="page"' : '' ?>><i class="bi <?= h($icono) ?>"></i><?= h($etiqueta) ?></a>
    <?php endforeach; ?>
  </nav>
  <article class="legal-card">
    <h1><?= h($titulo) ?></h1>
    <div class="legal-meta">Sistema de Soporte Técnico · Última actualización: <?= h(fechaLarga(LEGAL_ACTUALIZADO)) ?></div>
<?php
}

function legalLayoutEnd(): void
{
    ?>
    <h2>Contacto</h2>
    <p>Para consultas o solicitudes sobre este documento, escribe a la el área de soporte técnico a <a href="mailto:<?= h(LEGAL_CONTACTO) ?>"><?= h(LEGAL_CONTACTO) ?></a> o contacta al área de soporte técnico.</p>
  </article>
  <p class="legal-foot">Sistema de Soporte Técnico — proyecto de portafolio · <a href="privacidad.php">Privacidad</a> · <a href="terminos.php">Términos</a> · <a href="cookies.php">Cookies</a> · <a href="#" data-csp-print class="no-print">Imprimir</a></p>
</main>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
(function(){
  try{ if(localStorage.getItem('soporte-theme')==='dark'){document.documentElement.classList.add('dark-mode');document.body.classList.add('dark-mode');} }catch(e){}
})();
</script>
<script src="../assets/js/csp-handlers.js" defer></script></body>
</html>
<?php
}
