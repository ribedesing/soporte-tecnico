<?php
/**
 * Funciones auxiliares reutilizadas en varias vistas.
 */

/** Devuelve [clase_css, etiqueta] para pintar el badge de estatus. */
function badgeEstatus(string $estatus): array
{
    return match ($estatus) {
        'completado'        => ['badge-completado', 'Completado'],
        'en_proceso'        => ['badge-proceso', 'En proceso'],
        'pendiente_insumos' => ['badge-pendiente', 'Pend. insumos'],
        default             => ['badge-pendiente', ucfirst($estatus)],
    };
}

/** Clase del talón de ticket según el estatus (ver style.css .ticket-stub). */
function stubEstatus(string $estatus): string
{
    return match ($estatus) {
        'completado'        => 'st-completado',
        'en_proceso'        => 'st-proceso',
        'pendiente_insumos' => 'st-pendiente',
        default             => 'st-proceso',
    };
}

/** 95 -> "1h 35min", 25 -> "25 min" */
function minutosATexto(?int $min): string
{
    if ($min === null) return '—';
    if ($min < 60) return $min . ' min';
    return intdiv($min, 60) . 'h ' . ($min % 60) . 'min';
}

/** Escapa texto para imprimir seguro en HTML. */
function h(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** Devuelve la ruta relativa desde un módulo hasta la raíz de la aplicación. */
function rutaBaseAplicacion(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = trim(dirname($scriptName), '/');
    if ($scriptDir === '' || $scriptDir === '.') {
        return './';
    }

    $segmentos = explode('/', $scriptDir);
    $directoriosModulo = ['admin', 'analista', 'departamento', 'secretaria', 'includes'];
    for ($i = count($segmentos) - 1; $i >= 0; $i--) {
        if (in_array($segmentos[$i], $directoriosModulo, true)) {
            return str_repeat('../', count($segmentos) - $i);
        }
    }

    return './';
}

function cedulaValida(string $cedula): bool
{
    return (bool)preg_match('/^[VE]-\d{8}$/', strtoupper(trim($cedula)));
}

function telefonoValido(string $telefono): bool
{
    return (bool)preg_match('/^(0424|0414|0412|0422|0426|0416)-\d{7}$/', trim($telefono));
}

/** Devuelve las iniciales del nombre mostrado en el avatar. */
function iniciales(?string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre ?? ''), -1, PREG_SPLIT_NO_EMPTY);
    if (!$partes) {
        return 'U';
    }

    $iniciales = strtoupper(substr($partes[0], 0, 1));
    if (count($partes) > 1) {
        $iniciales .= strtoupper(substr($partes[count($partes) - 1], 0, 1));
    }
    return $iniciales;
}

/** "Lunes, 25 de agosto de 2026" sin depender de strftime() (obsoleta en PHP 8+). */
function fechaLarga(?string $fecha = null): string
{
    $dias  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $ts = $fecha ? strtotime($fecha) : time();
    return $dias[(int)date('w', $ts)] . ', ' . date('d', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
}

/** Número de hoja con formato de talón, ej. 129 -> "000129" */
function numeroHoja(int $id): string
{
    return str_pad((string)$id, 6, '0', STR_PAD_LEFT);
}

/* ---------------------------------------------------------
 * Registro de analistas (secretaría) — genera usuario,
 * contraseña temporal y código de referencia automáticamente,
 * para que la secretaria no tenga que inventarlos a mano.
 * --------------------------------------------------------- */

function quitarAcentos(string $s): string
{
    $s = strtolower($s);
    $mapa = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'];
    return strtr($s, $mapa);
}

/** "Mandy Peña" -> "mpena" (y si ya existe: "mpena2", "mpena3"…) */
function generarUsuario(PDO $pdo, string $nombreCompleto): string
{
    $partes = preg_split('/\s+/', trim(quitarAcentos($nombreCompleto)));
    $partes = array_values(array_filter($partes));
    $base = ($partes[0][0] ?? 'u') . ($partes[1] ?? $partes[0] ?? 'user');
    $base = preg_replace('/[^a-z0-9]/', '', $base) ?: 'user';

    $usuario = $base;
    $intento = 1;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario = :u');
    do {
        $stmt->execute(['u' => $usuario]);
        if ((int)$stmt->fetchColumn() === 0) break;
        $intento++;
        $usuario = $base . $intento;
    } while (true);

    return $usuario;
}

/** Contraseña temporal fácil de dictar por teléfono (sin caracteres ambiguos). */
function generarPasswordTemporal(int $largo = 8): string
{
    $alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // sin O/0, I/1/l para evitar confusión
    $clave = '';
    for ($i = 0; $i < $largo; $i++) {
        $clave .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }
    return $clave;
}

/** Código de referencia del analista, ej. "TKT-0427" (aún no se persiste en BD). */
function generarCodigoRegistro(): string
{
    return 'TKT-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}


/* ---------------------------------------------------------
 * Paginación reutilizable para todos los listados.
 * Se pagina a partir de 15 registros: si hay 15 o menos,
 * se muestran todos y no aparece ningún control.
 * --------------------------------------------------------- */

if (!defined('REGISTROS_POR_PAGINA')) {
    define('REGISTROS_POR_PAGINA', 15);
}

/**
 * Recorta un arreglo de filas a la página pedida en ?pagina=N.
 * Devuelve ['items','pagina','paginas','total','desde','hasta','porPagina','param'].
 */
function paginar(array $filas, int $porPagina = REGISTROS_POR_PAGINA, string $param = 'pagina'): array
{
    $total   = count($filas);
    $paginas = max(1, (int)ceil($total / $porPagina));
    $pagina  = (int)($_GET[$param] ?? 1);
    $pagina  = min(max($pagina, 1), $paginas);
    $offset  = ($pagina - 1) * $porPagina;

    return [
        'items'     => array_slice($filas, $offset, $porPagina),
        'pagina'    => $pagina,
        'paginas'   => $paginas,
        'total'     => $total,
        'desde'     => $total ? $offset + 1 : 0,
        'hasta'     => min($offset + $porPagina, $total),
        'porPagina' => $porPagina,
        'param'     => $param,
    ];
}

/** URL de la página actual con ?pagina=N conservando los filtros activos. */
function urlPagina(array $pg, int $n): string
{
    $query = $_GET;
    if ($n <= 1) {
        unset($query[$pg['param']]);
    } else {
        $query[$pg['param']] = $n;
    }
    $base = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    return $base . ($query ? '?' . http_build_query($query) : '');
}

/** Controles de paginación (Bootstrap). No imprime nada si no hace falta paginar. */
function renderPaginacion(array $pg): string
{
    if ($pg['total'] <= $pg['porPagina']) {
        return '';
    }

    $actual = $pg['pagina'];
    $ultima = $pg['paginas'];

    // Ventana de números: 1 … 4 5 [6] 7 8 … 20
    $nums = array_unique(array_merge([1, $ultima], range(max(1, $actual - 2), min($ultima, $actual + 2))));
    sort($nums);

    $li = static function (string $contenido, string $clase = '') {
        return '<li class="page-item ' . $clase . '">' . $contenido . '</li>';
    };

    $html  = '<nav class="paginacion-listado no-print" aria-label="Paginación">';
    $html .= '<div class="paginacion-info">Mostrando ' . $pg['desde'] . '–' . $pg['hasta'] . ' de ' . $pg['total'] . ' registros</div>';
    $html .= '<ul class="pagination pagination-sm mb-0">';

    $html .= $actual > 1
        ? $li('<a class="page-link" href="' . h(urlPagina($pg, $actual - 1)) . '" aria-label="Anterior"><i class="bi bi-chevron-left"></i></a>')
        : $li('<span class="page-link"><i class="bi bi-chevron-left"></i></span>', 'disabled');

    $previo = 0;
    foreach ($nums as $n) {
        if ($previo && $n - $previo > 1) {
            $html .= $li('<span class="page-link">…</span>', 'disabled');
        }
        $html .= $n === $actual
            ? $li('<span class="page-link">' . $n . '</span>', 'active')
            : $li('<a class="page-link" href="' . h(urlPagina($pg, $n)) . '">' . $n . '</a>');
        $previo = $n;
    }

    $html .= $actual < $ultima
        ? $li('<a class="page-link" href="' . h(urlPagina($pg, $actual + 1)) . '" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></a>')
        : $li('<span class="page-link"><i class="bi bi-chevron-right"></i></span>', 'disabled');

    return $html . '</ul></nav>';
}
