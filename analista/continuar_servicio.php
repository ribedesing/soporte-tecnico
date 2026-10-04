<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/helpers.php';

requerirRol(['analista']);

$id = (int)($_GET['id'] ?? 0);
$u  = usuarioActual();

$st = $pdo->prepare('
    SELECT id_hojas
    FROM hojas_servicio
    WHERE id_hojas = ?
      AND tecnico_id = ?
      AND estatus IN ("en_proceso", "pendiente_insumos")
');
$st->execute([$id, $u['id']]);

if (!$st->fetch()) {
    header('Location: mis_hojas.php');
    exit;
}

/* Se continúa la MISMA hoja. No se crea una hoja nueva. */
$_SESSION['hoja_en_curso'] = ['continuacion_id' => $id];

header('Location: hoja-servicio.php?continuar=' . $id);
exit;