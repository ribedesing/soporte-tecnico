<?php
require_once __DIR__ . '/../includes/legal_layout.php';
legalLayoutStart('Política de privacidad', 'privacidad');
?>
    <div class="legal-aviso"><i class="bi bi-info-circle"></i> Este texto describe cómo funciona hoy el sistema. Debe ser revisado por la consultoría jurídica de la institución antes de su publicación oficial.</div>

    <h2>1. Responsable</h2>
    <p>El responsable del tratamiento de los datos es la institución que administra este sistema de solicitud de soporte técnico y registro de hojas de servicio.</p>

    <h2>2. Datos que recopilamos</h2>
    <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead><tr><th>Categoría</th><th>Datos</th><th>Origen</th></tr></thead>
      <tbody>
        <tr><td>Identificación y contacto</td><td>Nombre, apellido, cédula, teléfono, correo electrónico</td><td>Registro de cuenta o alta hecha por la secretaría</td></tr>
        <tr><td>Datos institucionales</td><td>Departamento u oficina, rol dentro del sistema</td><td>Registro / administración</td></tr>
        <tr><td>Credenciales</td><td>Usuario y contraseña (se guarda solo un hash, nunca la clave en texto)</td><td>Registro / administración</td></tr>
        <tr><td>Foto de perfil</td><td>Imagen de avatar (opcional)</td><td>Perfil del usuario</td></tr>
        <tr><td>Actividad del servicio</td><td>Tickets, hojas de servicio, descripción del trabajo, hora de inicio y fin, firma en pantalla, fotos o documentos adjuntos como evidencia</td><td>Uso del sistema</td></tr>
        <tr><td>Datos técnicos y de seguridad</td><td>Dirección IP, intentos de acceso fallidos, registro de accesos y acciones (auditoría), última actividad, notificaciones</td><td>Generados automáticamente</td></tr>
      </tbody>
    </table>
    </div>
    <p>No solicitamos datos de menores de edad, datos bancarios ni categorías sensibles. Te pedimos no incluirlos en descripciones ni adjuntos.</p>

    <h2>3. Para qué los usamos</h2>
    <ul>
      <li>Crear y autenticar tu cuenta, y confirmar tu correo.</li>
      <li>Registrar, asignar y dar seguimiento a solicitudes de soporte técnico.</li>
      <li>Documentar los servicios prestados mediante hojas de servicio (con firma y evidencias).</li>
      <li>Evaluar el rendimiento del personal técnico y generar reportes internos.</li>
      <li>Enviarte correos operativos: confirmación de cuenta y recuperación de contraseña.</li>
      <li>Proteger el sistema: bloqueo tras intentos fallidos, protección CSRF y auditoría de accesos.</li>
    </ul>
    <p>No usamos tus datos para publicidad ni perfiles comerciales, y no los vendemos.</p>

    <h2>4. Quién puede ver tus datos</h2>
    <ul>
      <li><strong>Administración y secretaría:</strong> acceso a usuarios, tickets, hojas y reportes según los permisos de su rol.</li>
      <li><strong>Analistas técnicos:</strong> ven los tickets y hojas que atienden y los datos de contacto necesarios para prestar el servicio.</li>
      <li><strong>Departamentos u oficinas:</strong> ven únicamente sus propios tickets y hojas.</li>
    </ul>

    <h2>5. Servicios de terceros</h2>
    <p>Aunque el sistema corre en la infraestructura de la institución, algunos recursos vienen de terceros y pueden recibir tu dirección IP y datos técnicos del navegador al cargar la página:</p>
    <ul>
      <li><strong>Google Fonts</strong> (tipografías) y <strong>jsDelivr</strong> (Bootstrap e íconos).</li>
      <li><strong>Gmail (SMTP de Google)</strong> para enviar los correos de confirmación y recuperación de contraseña; tu correo y el contenido del mensaje pasan por ese servicio.</li>
    </ul>

    <h2>6. Conservación</h2>
    <p>Conservamos los datos mientras tu cuenta esté activa y durante el tiempo que exijan las normas de archivo y control internas sobre los registros de servicio. Los tokens de confirmación y recuperación de contraseña son temporales.</p>

    <h2>7. Seguridad</h2>
    <p>Aplicamos medidas razonables: contraseñas cifradas con hash, sesiones con cookie protegida, tokens CSRF, bloqueo temporal tras 3 intentos fallidos y carpetas de archivos con acceso restringido. Ningún sistema es infalible; protege tu contraseña y cierra sesión en equipos compartidos.</p>

    <h2>8. Tus derechos</h2>
    <p>Conforme a la Constitución de la República Bolivariana de Venezuela (artículos 28 y 60) y a la normativa aplicable, puedes solicitar acceso a tus datos personales, su actualización, rectificación o, cuando proceda, su eliminación. Escribe al contacto indicado abajo indicando tu usuario y cédula. Algunos registros de servicio pueden conservarse por obligación de control institucional.</p>

    <h2>9. Cambios a esta política</h2>
    <p>Podemos actualizar este documento; la fecha de última actualización aparece arriba. Los cambios importantes se comunicarán en el sistema.</p>
<?php legalLayoutEnd();
