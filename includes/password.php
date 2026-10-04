<?php
function passwordValida(string $password): bool
{
    return strlen($password) >= 12
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/\d/', $password)
        && preg_match('/[^\p{L}\p{N}\s]/u', $password);
}
function mensajePassword(): string
{
    return 'La contraseña debe tener mínimo 12 caracteres e incluir mayúscula, minúscula, número y símbolo o signo de puntuación.';
}
