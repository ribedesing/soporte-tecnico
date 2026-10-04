<?php
require_once __DIR__ . '/../includes/legal_layout.php';
legalLayoutStart('Política de cookies', 'cookies');
?>
    <h2>1. Qué son</h2>
    <p>Las cookies y el almacenamiento local son pequeños datos que el sitio guarda en tu navegador para funcionar o recordar preferencias.</p>

    <h2>2. Lo que usa este sistema</h2>
    <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead><tr><th>Nombre</th><th>Tipo</th><th>Finalidad</th><th>Duración</th></tr></thead>
      <tbody>
        <tr><td><code>PHPSESSID</code></td><td>Cookie esencial</td><td>Mantiene tu sesión iniciada y el token de seguridad CSRF. Es HttpOnly, SameSite=Lax y Secure cuando se usa HTTPS.</td><td>Sesión (se elimina al cerrar el navegador o al cerrar sesión)</td></tr>
        <tr><td><code>soporte-theme</code></td><td>Almacenamiento local (preferencia)</td><td>Recuerda si prefieres modo claro u oscuro.</td><td>Hasta que lo borres</td></tr>
      </tbody>
    </table>
    </div>

    <h2>3. Lo que NO usamos</h2>
    <p>No usamos cookies de publicidad, analítica ni seguimiento entre sitios. Por eso no mostramos un banner de consentimiento: solo se usan elementos estrictamente necesarios o pedidos por ti (el tema).</p>

    <h2>4. Recursos de terceros</h2>
    <p>Google Fonts y jsDelivr entregan tipografías, estilos e íconos. Al cargarlos, esos proveedores pueden ver tu dirección IP y datos del navegador, y aplican sus propias políticas.</p>

    <h2>5. Notificaciones del navegador</h2>
    <p>Los analistas pueden recibir avisos de nuevos tickets. El navegador te pide permiso para mostrarlos; puedes revocarlo cuando quieras desde los ajustes del sitio en tu navegador.</p>

    <h2>6. Cómo controlarlas</h2>
    <p>Puedes borrar cookies y datos del sitio desde la configuración de tu navegador. Si bloqueas la cookie de sesión no podrás iniciar sesión; si borras el tema, el sistema vuelve al modo claro.</p>
<?php legalLayoutEnd();
