<?php

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$segments = explode('/', str_replace('\\', '/', $path));

foreach ($segments as $segment) {
    $lowerSegment = strtolower($segment);
    if ($segment === '..' || str_starts_with($segment, '.') || in_array($lowerSegment, ['uploads', 'logs'], true)) {
        http_response_code(404);
        exit;
    }
}

return false;