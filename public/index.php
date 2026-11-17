<?php

declare(strict_types=1);

use App\Controllers\HomeController;

require dirname(__DIR__) . '/app/Controllers/HomeController.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

header('Content-Type: text/html; charset=UTF-8');

if ($path !== '/' && $path !== '/index.php') {
    http_response_code(404);
    if ($method !== 'HEAD') {
        require dirname(__DIR__) . '/app/Views/not-found.php';
    }
    return;
}

if ($method !== 'GET' && $method !== 'HEAD') {
    http_response_code(405);
    header('Allow: GET, HEAD');
    return;
}

if ($method === 'GET') {
    (new HomeController())->index();
}
