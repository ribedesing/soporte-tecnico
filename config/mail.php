<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function configuracionMail(string $nombre, string $predeterminado = ''): string
{
    $valor = getenv($nombre);
    if ($valor !== false && $valor !== '') {
        return $valor;
    }

    $archivo = __DIR__ . '/../.env';
    if (is_readable($archivo)) {
        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $contenido] = explode('=', $linea, 2);
            if (trim($clave) === $nombre) {
                return trim($contenido, " \t\"'");
            }
        }
    }

    return $predeterminado;
}

function urlAplicacion(): ?string
{
    $url = trim(configuracionMail('APP_URL'));
    $partes = parse_url($url);
    if (!is_array($partes)
        || !isset($partes['scheme'], $partes['host'])
        || !in_array(strtolower($partes['scheme']), ['http', 'https'], true)
        || isset($partes['user'])
        || isset($partes['pass'])
        || isset($partes['query'])
        || isset($partes['fragment'])) {
        return null;
    }

    return rtrim($url, '/');
}

function enviarCorreoRecuperacion(string $destinatario, string $nombre, string $url): bool
{
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = configuracionMail('SMTP_USERNAME');
        if ($mail->Username === '') throw new RuntimeException('SMTP_USERNAME no configurado.');
        $mail->Password = preg_replace('/\s+/', '', configuracionMail('SMTP_PASSWORD'));
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($mail->Username, 'Soporte Técnico');
        $mail->addAddress($destinatario, $nombre);
        $mail->isHTML(true);
        $mail->Subject = 'Restablecimiento de contraseña - Soporte Técnico';
        $mail->Body = '<p>Hola ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '.</p>'
            . '<p>Solicitaste restablecer tu contraseña. Este enlace es válido durante 60 minutos:</p>'
            . '<p><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">Restablecer contraseña</a></p>'
            . '<p>Si no realizaste esta solicitud, ignora este mensaje.</p>';
        return $mail->send();
    } catch (Throwable $e) {
        error_log('No se pudo enviar el correo de recuperación: ' . $e->getMessage());
        return false;
    }
}
function enviarCorreoConfirmacion(string $destinatario,string $nombre,string $url): bool {try{$mail=new PHPMailer(true);$mail->isSMTP();$mail->Host='smtp.gmail.com';$mail->SMTPAuth=true;$mail->Username=configuracionMail('SMTP_USERNAME');if($mail->Username==='') throw new RuntimeException('SMTP_USERNAME no configurado.');$mail->Password=preg_replace('/\s+/','',configuracionMail('SMTP_PASSWORD'));$mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS;$mail->Port=587;$mail->CharSet='UTF-8';$mail->setFrom($mail->Username,'Soporte Técnico');$mail->addAddress($destinatario,$nombre);$mail->isHTML(true);$mail->Subject='Confirma tu correo - Soporte Técnico';$mail->Body='<p>Hola '.htmlspecialchars($nombre,ENT_QUOTES,'UTF-8').'.</p><p>Confirma tu correo electrónico haciendo clic en el siguiente enlace:</p><p><a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'">Confirmar correo</a></p><p>El enlace es válido durante 24 horas.</p>';return $mail->send();}catch(Throwable $e){error_log('Error confirmación correo: '.$e->getMessage());return false;}}
