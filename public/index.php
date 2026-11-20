<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\HomeController;
use App\Controllers\PublicPagesController;
use App\Controllers\PublicRequestsController;
use Dotenv\Exception\ExceptionInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

header('Content-Type: text/html; charset=UTF-8');

if (is_string($path) && str_starts_with($path, '/index.php/')) {
    $path = substr($path, strlen('/index.php'));
}

if ($path !== '/' && $path !== '/index.php') {
    try {
        if (in_array($path, ['/reservation', '/devis'], true)) {
            (new PublicRequestsController())->dispatch($path, $method);
            return;
        }
        if (is_string($path) && (new PublicPagesController())->dispatch($path, $method)) {
            return;
        }
    } catch (RuntimeException | ExceptionInterface $error) {
        publicUnavailable($method, $error);
        return;
    }

    http_response_code(404);
    if ($method !== 'HEAD') {
        require dirname(__DIR__) . '/app/Views/not-found.php';
    }
    return;
}

$route = $_GET['route'] ?? null;
if (in_array($route, [
    'admin', 'admin-login', 'admin-logout', 'admin-catalogue', 'admin-prestation', 'admin-categorie',
    'admin-a-propos', 'admin-a-propos-entree', 'admin-a-propos-apercu', 'admin-a-propos-image', 'admin-demandes', 'admin-clients', 'admin-commandes', 'admin-finances',
], true)) {
    (new AdminController())->dispatch($route, $method);
    return;
}

if ($method !== 'GET' && $method !== 'HEAD') {
    http_response_code(405);
    header('Allow: GET, HEAD');
    return;
}

if ($method === 'GET') {
    try {
        (new HomeController())->index();
    } catch (RuntimeException | ExceptionInterface $error) {
        publicUnavailable($method, $error);
    }
}

function publicUnavailable(string $method, Throwable $error): void
{
    error_log('Erreur du site public : ' . $error::class);
    http_response_code(503);
    header('Cache-Control: no-store');

    if ($method !== 'HEAD') {
        echo 'Cette page est temporairement indisponible.';
    }
}
