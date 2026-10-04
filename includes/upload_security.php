<?php
function validarImagenSubida(array $archivo, array $mimesPermitidos, int $maxBytes): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Archivo no válido.');
    if ((int)($archivo['size'] ?? 0) <= 0 || (int)$archivo['size'] > $maxBytes) throw new RuntimeException('Archivo demasiado grande.');
    $tmp = $archivo['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp)) throw new RuntimeException('Carga no válida.');
    $info = @getimagesize($tmp);
    if ($info === false || empty($info['mime']) || !isset($mimesPermitidos[$info['mime']])) throw new RuntimeException('La imagen no tiene una estructura válida.');
    $mimeReal = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!isset($mimesPermitidos[$mimeReal]) || $mimeReal !== $info['mime']) throw new RuntimeException('Tipo de imagen no permitido.');
    if (($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1 || ($info[0] ?? 0) > 8000 || ($info[1] ?? 0) > 8000) throw new RuntimeException('Dimensiones de imagen no permitidas.');
    return $mimesPermitidos[$mimeReal];
}

function validarAdjuntoSubido(array $archivo, array $mimesPermitidos, int $maxBytes): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Adjunto no válido.');
    if ((int)($archivo['size'] ?? 0) <= 0 || (int)$archivo['size'] > $maxBytes) throw new RuntimeException('Adjunto demasiado grande.');
    if (!is_uploaded_file($archivo['tmp_name'] ?? '')) throw new RuntimeException('Carga no válida.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!isset($mimesPermitidos[$mime])) throw new RuntimeException('Tipo de adjunto no permitido.');
    return $mimesPermitidos[$mime];
}
