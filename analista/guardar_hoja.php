<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/upload_security.php';

requerirRol(['analista']);
$u = usuarioActual();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['hoja_en_curso'])) {
    header('Location: iniciar-servicio.php'); exit;
}

if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: hoja-servicio.php?error=csrf'); exit;
}

$enCurso = $_SESSION['hoja_en_curso'];

/* =====================================================
   CONTINUACIÓN: se actualiza la misma hoja y se agrega
   la descripción de culminación.
   ===================================================== */
if (!empty($enCurso['continuacion_id'])) {

    $id = (int)$enCurso['continuacion_id'];
    $culminacion = trim($_POST['descripcion_culminacion'] ?? '');

    if ($culminacion === '') {
        header('Location: hoja-servicio.php?continuar=' . $id . '&error=4');
        exit;
    }

    $pdo->beginTransaction();

    try {
        $st = $pdo->prepare('
            SELECT *
            FROM hojas_servicio
            WHERE id_hojas = ?
              AND tecnico_id = ?
              AND estatus IN ("en_proceso", "pendiente_insumos")
            FOR UPDATE
        ');
        $st->execute([$id, $u['id']]);
        $h = $st->fetch();

        if (!$h) {
            throw new RuntimeException('La hoja ya no está disponible para culminar.');
        }

        $ahora     = date('H:i:s');
        $inicioTs  = strtotime($h['fecha'] . ' ' . $h['hora_inicio']);
        $total     = max(0, intdiv(time() - $inicioTs, 60));

        $descripcion = rtrim($h['descripcion'])
            . "\n\n--- CULMINACIÓN DEL SERVICIO (" . date('d/m/Y H:i') . ") ---\n"
            . $culminacion;

        $pdo->prepare('
            UPDATE hojas_servicio
            SET descripcion = ?,
                estatus = "completado",
                hora_fin = ?,
                tiempo_total_minutos = ?
            WHERE id_hojas = ?
        ')->execute([$descripcion, $ahora, $total, $id]);

        $pdo->prepare('
            INSERT INTO auditoria_accesos (usuario_id, accion, detalle)
            VALUES (?, "culminar_hoja", ?)
        ')->execute([$u['id'], 'Hoja de servicio #' . $id . ' culminada']);

        $destinatarios = $pdo->prepare('SELECT u.id_usuarios FROM usuarios u JOIN roles r ON r.id_roles = u.rol_id JOIN perfiles_usuarios p ON p.usuario_id = u.id_usuarios WHERE r.nombre = "departamento" AND p.departamento_id = ?');
        $destinatarios->execute([(int)$h['departamento_solicitante_id']]);
        $crearAviso = $pdo->prepare('INSERT INTO notificaciones (usuario_id, titulo, mensaje, url, leida) VALUES (?, ?, ?, ?, 0)');
        $urlHoja = 'departamento/ver_hoja.php?id=' . $id;
        foreach ($destinatarios->fetchAll(PDO::FETCH_COLUMN) as $usuarioId) {
            $crearAviso->execute([(int)$usuarioId, 'Servicio culminado', 'La hoja de servicio #' . $id . ' fue culminada. Puedes verificarla.', $urlHoja]);
        }

        $pdo->commit();

        unset($_SESSION['hoja_en_curso']);
        header('Location: ver_hoja.php?id=' . $id . '&culminada=1');
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header('Location: mis_hojas.php?error=culminar');
        exit;
    }
}


/* =====================================================
   CREACIÓN NUEVA HOJA
   ===================================================== */

$descripcion      = trim($_POST['descripcion'] ?? '');
$estatus          = $_POST['estatus'] ?? '';
$atendidoNombre   = trim($_POST['usuario_atendido_nombre'] ?? '');
$otroEspecifique  = trim($_POST['otro_especifique'] ?? '') ?: null;
$tipos            = $_POST['tipos'] ?? [];
$firmaDataUrl     = $_POST['firma_dataurl'] ?? '';

$estatusValidos = ['completado', 'en_proceso', 'pendiente_insumos'];

if ($descripcion === '' || $atendidoNombre === '' || !in_array($estatus, $estatusValidos, true)) {
    header('Location: hoja-servicio.php?error=1');
    exit;
}

if (empty($tipos)) {
    header('Location: hoja-servicio.php?error=2');
    exit;
}

if ($firmaDataUrl === '') {
    header('Location: hoja-servicio.php?error=3');
    exit;
}

$horaFin    = date('H:i:s');
$horaInicio = $enCurso['hora_inicio'];
$inicioTs   = strtotime($enCurso['fecha'] . ' ' . $horaInicio);
$tiempoTotal = max(0, intdiv(time() - $inicioTs, 60));

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO hojas_servicio
            (tecnico_id, departamento_solicitante_id, fecha,
             hora_inicio, hora_fin, otro_especifique, descripcion,
               estatus, usuario_atendido_nombre, tiempo_total_minutos, ticket_id)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $u['id'],
        $enCurso['departamento_id'],
        $enCurso['fecha'],
        $horaInicio,
        $horaFin,
        $otroEspecifique,
        $descripcion,
        $estatus,
        $atendidoNombre,
        $tiempoTotal,
        !empty($enCurso['ticket_id']) ? (int)$enCurso['ticket_id'] : null
    ]);

    $hojaId = (int)$pdo->lastInsertId();

    /* ---------- Evidencia (solo una) ---------- */
    if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] !== UPLOAD_ERR_NO_FILE) {

        $dir = __DIR__ . '/../uploads/evidencias';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $f = $_FILES['evidencia'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $mensajeCarga = match ((int)$f['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño máximo permitido de 12 MB.',
                UPLOAD_ERR_PARTIAL => 'La imagen se cargó incompleta. Comprueba la conexión y vuelve a intentarlo.',
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo guardar temporalmente la imagen.',
                default => 'No se pudo subir la imagen de evidencia. Intenta tomarla nuevamente.',
            };
            throw new RuntimeException($mensajeCarga);
        }

        if ($f['size'] > 12 * 1024 * 1024) {
            throw new RuntimeException('La imagen supera el tamaño máximo permitido de 12 MB.');
        }

        $ext = validarImagenSubida($f, [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ], 12 * 1024 * 1024);

        $dest = 'hoja_' . $hojaId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

        if (move_uploaded_file($f['tmp_name'], $dir . '/' . $dest)) {
            $pdo->prepare('
                INSERT INTO hoja_servicio_evidencias (hoja_id, archivo)
                VALUES (?, ?)
            ')->execute([$hojaId, 'uploads/evidencias/' . $dest]);
        }
    }

    /* ---------- Tipos de servicio ---------- */
    $stmtTipo = $pdo->prepare('
        INSERT INTO hoja_servicio_tipo (hoja_id, tipo_servicio_id)
        VALUES (?, ?)
    ');
    foreach ($tipos as $tipoId) {
        $stmtTipo->execute([$hojaId, (int)$tipoId]);
    }

    /* ---------- Firma ---------- */
    $rutaFirma = null;
    if ($firmaDataUrl !== '') {
        if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=\r\n]+)$/', $firmaDataUrl, $m)) {
            throw new RuntimeException('Firma no válida.');
        }
        $bin = base64_decode($m[1], true);
        if ($bin === false || strlen($bin) > 2 * 1024 * 1024) throw new RuntimeException('Firma demasiado grande.');
        $tmpFirma = tempnam(sys_get_temp_dir(), 'firma_');
        if ($tmpFirma === false || file_put_contents($tmpFirma, $bin) === false) throw new RuntimeException('No se pudo validar la firma.');
        $infoFirma = @getimagesize($tmpFirma);
        @unlink($tmpFirma);
        if ($infoFirma === false || ($infoFirma['mime'] ?? '') !== 'image/png') throw new RuntimeException('La firma no tiene una estructura PNG válida.');
        $nom = 'hoja_' . $hojaId . '_' . bin2hex(random_bytes(6)) . '.png';
        if (file_put_contents(__DIR__ . '/../uploads/firmas/' . $nom, $bin) !== false) {
            $rutaFirma = 'uploads/firmas/' . $nom;
        }
    }

    if ($rutaFirma) {
        $pdo->prepare('
            UPDATE hojas_servicio
            SET firma_imagen = ?
            WHERE id_hojas = ?
        ')->execute([$rutaFirma, $hojaId]);
    }

    /* ---------- Auditoría ---------- */
    $pdo->prepare('
        INSERT INTO auditoria_accesos (usuario_id, accion, detalle)
        VALUES (?, "crear_hoja", ?)
    ')->execute([$u['id'], 'Hoja de servicio #' . $hojaId]);

    if (!empty($enCurso['ticket_id'])) {
        $pdo->prepare("UPDATE tickets SET estado=?, fecha_inicio=COALESCE(fecha_inicio, NOW()), fecha_cierre=? WHERE id_tickets=? AND analista_id=?")
            ->execute([$estatus === 'completado' ? 'completado' : 'en_proceso', $estatus === 'completado' ? date('Y-m-d H:i:s') : null, (int)$enCurso['ticket_id'], $u['id']]);
    }

    if ($estatus === 'completado') {
        $destinatarios = $pdo->prepare('SELECT u.id_usuarios FROM usuarios u JOIN roles r ON r.id_roles = u.rol_id JOIN perfiles_usuarios p ON p.usuario_id = u.id_usuarios WHERE r.nombre = "departamento" AND p.departamento_id = ?');
        $destinatarios->execute([(int)$enCurso['departamento_id']]);
        $crearAviso = $pdo->prepare('INSERT INTO notificaciones (usuario_id, titulo, mensaje, url, leida) VALUES (?, ?, ?, ?, 0)');
        $urlHoja = 'departamento/ver_hoja.php?id=' . $hojaId;
        foreach ($destinatarios->fetchAll(PDO::FETCH_COLUMN) as $usuarioId) {
            $crearAviso->execute([(int)$usuarioId, 'Servicio culminado', 'La hoja de servicio #' . $hojaId . ' fue culminada. Puedes verificarla.', $urlHoja]);
        }

        $secretarias = $pdo->query('SELECT u.id_usuarios FROM usuarios u JOIN roles r ON r.id_roles = u.rol_id WHERE r.nombre = "secretaria"')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($secretarias as $usuarioId) {
            $crearAviso->execute([(int)$usuarioId, 'Servicio culminado', 'La hoja de servicio #' . $hojaId . ' fue culminada por un analista.', 'secretaria/hojas.php?hoja=' . $hojaId . '&aviso=culminado']);
        }
    }

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    emitirLogError('No se pudo guardar la hoja de servicio.', ['usuario_id' => $u['id'], 'code' => $e->getCode()]);
    header('Location: mis_hojas.php?error=guardar');
    exit;
}

unset($_SESSION['hoja_en_curso']);
header('Location: dashboard.php?guardado=' . $hojaId);
exit;