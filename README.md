# Sistema de Soporte Técnico

Plataforma web de gestión de **tickets y hojas de servicio técnico** para una institución pública. Centraliza la solicitud de soporte por parte de los departamentos, la atención en campo por analistas y la supervisión desde secretaría y administración.

> **Demo en vivo:** [URL_DE_LA_DEMO](URL_DE_LA_DEMO) &nbsp;·&nbsp; entorno de demostración con **datos 100 % ficticios**.
> El código se publica únicamente con fines de evaluación (ver [LICENSE](LICENSE)).

<!-- Reemplaza URL_DE_LA_DEMO por el enlace real cuando despliegues la demo. -->

## Capturas

<!-- Guarda las imágenes en docs/screenshots/ con estos nombres (o cambia las rutas). -->

| Inicio de sesión | Panel del departamento |
|---|---|
| ![Login](docs/screenshots/01-login.png) | ![Panel departamento](docs/screenshots/02-departamento.png) |

| Hoja de servicio del analista (móvil) | Panel de administración |
|---|---|
| ![Hoja de servicio](docs/screenshots/03-hoja-servicio.png) | ![Panel admin](docs/screenshots/04-admin.png) |

<!-- Opcional: un GIF corto del flujo ticket → analista → hoja de servicio → PDF. -->

## Probar la demo

Usa cualquiera de estas cuentas ficticias para recorrer el sistema desde cada rol:

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin.demo` | `Demo-Admin-2026!` |
| Secretaría | `secretaria.demo` | `Demo-Secretaria-2026!` |
| Analista | `analista.demo` | `Demo-Analista-2026!` |
| Oficina / Departamento | `oficina.demo` | `Demo-Oficina-2026!` |

Flujo sugerido (≈ 3 minutos): entra como **Oficina** y crea un ticket → entra como **Analista**, toma el ticket e inicia el servicio → completa la **hoja de servicio** (descripción, evidencias, firma) → revisa el resultado como **Secretaría** y **Administrador**.

## Roles del sistema

| Rol | Qué hace |
|---|---|
| **Departamento / Oficina** | Crea tickets de soporte, hace seguimiento a sus solicitudes y consulta las hojas de servicio generadas para ellos. |
| **Analista** | Toma tickets disponibles, inicia y finaliza el servicio en campo, llena la hoja de servicio (tipo, descripción, evidencias, firma) y consulta su historial. |
| **Secretaría** | Supervisa tickets y hojas de servicio de todos los analistas, registra nuevas cuentas de analista y da seguimiento a su desempeño. |
| **Administrador** | Gestiona usuarios, departamentos, roles y permisos, catálogos del sistema, reportes generales, bitácora de auditoría y el panel de seguridad. |

## Funcionalidades principales

- **Flujo de servicio mobile-first:** "Iniciar servicio" guarda la hora de inicio automáticamente; al guardar la hoja, la hora de fin se registra sola.
- **Hojas de servicio con evidencias fotográficas y firma digital**, exportables a PDF desde el navegador (sin librerías externas de PDF).
- **Registro de analistas con credenciales de un solo uso:** secretaría crea la cuenta, el sistema genera usuario y contraseña temporal y los muestra una sola vez junto con un código QR para abrir el sistema en el celular y agregarlo a la pantalla de inicio (vía `manifest.json`).
- **Notificaciones casi en tiempo real** (sondeo por AJAX): tickets sin atender, servicios pendientes de culminar, perfiles incompletos y actividad relevante.
- **Confirmación de correo obligatoria** para departamentos y analistas, con reenvío del enlace.
- **Reportes y bitácora de auditoría** de acciones sensibles.
- **Roles y permisos configurables** desde el panel de administración.
- **Modo claro/oscuro** persistente y diseño responsive (barra inferior en móvil, menú lateral en escritorio).
- **Páginas legales:** privacidad, términos y cookies.

## Seguridad

- Autenticación con límite de intentos por IP y por cuenta, y comparación de contraseña en tiempo constante para no revelar si un usuario existe.
- Protección CSRF en formularios y acciones que modifican datos.
- Cabeceras de seguridad (CSP con nonce, `X-Frame-Options`, `HSTS` condicional a HTTPS, `Referrer-Policy`, `Permissions-Policy`).
- Sesiones endurecidas (`httponly`, `samesite`, cookies seguras en HTTPS) y cierre por inactividad.
- Política de contraseñas: mínimo 12 caracteres con mayúscula, minúscula, número y símbolo.
- Carpetas internas (`config/`, `database/`, `includes/`, `logs/`, `herramientas/`) bloqueadas para acceso directo desde el navegador.
- Validación real del tipo de archivo (no solo la extensión) en toda subida.
- Los errores nunca se muestran al usuario final: se registran en `logs/`.

## Tecnologías

PHP 8 · MySQL / MariaDB (PDO con consultas preparadas) · Bootstrap 5 · Bootstrap Icons · PHPMailer (Composer). Sin frameworks de frontend ni paso de compilación: el HTML se genera en el servidor.

## Estructura del proyecto

```
admin/          usuarios, departamentos, roles/permisos, catálogos, reportes, auditoría y seguridad
secretaria/     supervisión de tickets, hojas de servicio y analistas
analista/       panel del técnico: tickets, servicio en campo, hojas
departamento/   creación y seguimiento de tickets por oficina
auth/           inicio de sesión, recuperación y restablecimiento de contraseña
legal/          privacidad, términos y condiciones, cookies
includes/       sesiones/roles, seguridad, perfil, notificaciones, correo y vista imprimible
config/         conexión a la base de datos y correo
database/       schema.sql (estructura) y seed_demo.sql (datos ficticios)
assets/         estilos, íconos y scripts del frontend
uploads/        avatares, firmas, evidencias y adjuntos (no versionados)
manifest.json   permite "Agregar a pantalla de inicio" en el celular
```

## Ejecutarlo en local

Requisitos: PHP 8+, MySQL o MariaDB, Composer.

```bash
# 1. Dependencias
composer install

# 2. Configuración
cp .env.example .env        # edita APP_URL y las credenciales de la base de datos

# 3. Base de datos
mysql -u root -p -e "CREATE DATABASE soporte_tecnico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p soporte_tecnico < database/schema.sql
mysql -u root -p soporte_tecnico < database/seed_demo.sql   # opcional: datos y cuentas de demo

# 4. Servidor de desarrollo
php -S localhost:8000 router.php
```

Abre `http://localhost:8000` e inicia sesión con alguna de las cuentas demo.

**Notas**

- El envío de correos (confirmación y recuperación de contraseña) usa SMTP con una clave de aplicación; es opcional en local si usas las cuentas demo, que ya vienen confirmadas.
- En producción, `APP_FORCE_HTTPS` y `APP_ENV=production` se definen en el **entorno de PHP / servidor**, no en el archivo `.env`.
- `database/seed_demo.sql` es solo para una base nueva de demostración; no lo ejecutes sobre datos reales.

## Mi aporte

<!-- Completa esta sección con tus palabras: es lo que más leen los reclutadores. Ejemplos de qué contar: -->

- **Contexto:** [para quién lo hiciste y qué problema resolvía].
- **Mi rol:** [diseño, backend, frontend, base de datos, despliegue…].
- **Retos técnicos:** [p. ej. flujo de servicio en campo, seguridad de sesiones, subida segura de archivos].
- **Resultado:** [quién lo usa, qué mejoró].

## Licencia

Todos los derechos reservados. El código es visible únicamente para evaluación; no se autoriza su uso, copia ni distribución sin permiso por escrito. Consulta [LICENSE](LICENSE).

## Contacto

[TU NOMBRE] · [LinkedIn] · [correo]
