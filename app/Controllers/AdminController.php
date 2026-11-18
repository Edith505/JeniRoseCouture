<?php

declare(strict_types=1);

namespace App\Controllers;

use App\About\AboutContent;
use App\Auth\AdminAccess;
use App\Auth\AdminSession;
use App\Catalog\Catalogue;
use App\Dashboard\Overview;
use App\Dashboard\Period;
use App\Database\Connection;
use DomainException;
use App\Requests\ClientRequests;
use Throwable;

final class AdminController
{
    private const LOGIN = '/index.php?route=admin-login';
    private const HOME = '/index.php?route=admin';

    public function dispatch(string $route, string $method): void
    {
        header('Cache-Control: no-store');
        $scripts = $route === 'admin' ? "; script-src 'self'" : '';
        header("Content-Security-Policy: default-src 'none'; style-src 'self'; font-src 'self'; img-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'" . $scripts);
        header('Referrer-Policy: no-referrer');

        $allowed = match ($route) {
            'admin-login' => ['GET', 'HEAD', 'POST'],
            'admin-logout' => ['POST'],
            'admin', 'admin-catalogue', 'admin-a-propos-apercu', 'admin-a-propos-image' => ['GET', 'HEAD'],
            'admin-prestation', 'admin-categorie', 'admin-a-propos', 'admin-a-propos-entree', 'admin-demandes' => ['GET', 'HEAD', 'POST'],
        };

        if (!in_array($method, $allowed, true)) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowed));
            return;
        }

        try {
            AdminSession::start();

            if ($method === 'POST' && !AdminSession::acceptsToken($_POST['csrf'] ?? null)) {
                http_response_code(403);
                $this->loginForm('Formulaire expiré. Réessayez.');
                return;
            }

            match ($route) {
                'admin-login' => $this->login($method),
                'admin-logout' => $this->logout(),
                'admin' => $this->home($method),
                'admin-catalogue', 'admin-prestation', 'admin-categorie',
                'admin-a-propos', 'admin-a-propos-entree', 'admin-a-propos-apercu', 'admin-a-propos-image', 'admin-demandes'
                    => $this->feature($route, $method),
            };
        } catch (Throwable $error) {
            error_log('Erreur de l’administration : ' . $error::class);
            http_response_code(503);
            if ($method !== 'HEAD') {
                echo 'Administration temporairement indisponible.';
            }
        }
    }

    private function login(string $method): void
    {
        if ($method === 'HEAD') {
            return;
        }

        if ($method === 'GET') {
            $this->loginForm();
            return;
        }

        $access = new AdminAccess(Connection::open());
        if (!$access->recordAttempt($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            http_response_code(429);
            $this->loginForm('Trop de tentatives. Réessayez dans quelques minutes.');
            return;
        }

        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        $userId = null;

        if (is_string($email) && is_string($password)
            && strlen($email) <= 254 && strlen($password) <= 72
            && !str_contains($password, "\0") && filter_var(trim($email), FILTER_VALIDATE_EMAIL)
        ) {
            $userId = $access->authenticate(trim($email), $password);
        }

        if ($userId === null) {
            http_response_code(401);
            $this->loginForm('Identifiants incorrects.');
            return;
        }

        AdminSession::signIn($userId);
        error_log("Connexion administration : utilisateur {$userId}");
        self::redirect(self::HOME);
    }

    private function home(string $method): void
    {
        $userId = AdminSession::userId();
        if ($userId === null) {
            self::redirect(self::LOGIN);
            return;
        }

        $database = Connection::open();
        $access = new AdminAccess($database);
        if (!$access->can($userId, 'admin.access')) {
            AdminSession::clear();
            self::redirect(self::LOGIN);
            return;
        }

        AdminSession::touch();
        if ($method === 'GET') {
            $csrf = AdminSession::token();
            $catalogueNavigation = $access->can($userId, 'catalog.manage');
            $aboutNavigation = $access->can($userId, 'about.manage');
            $requestNavigation = $access->can($userId, 'requests.manage');
            $profile = $database->prepare('SELECT display_name FROM users WHERE id = ?');
            $profile->execute([$userId]);
            $adminName = (string) $profile->fetchColumn();
            $activeAdminPage = 'overview';
            $overview = null;
            $period = null;
            $periodError = null;
            if ($requestNavigation) {
                try {
                    $period = Period::fromQuery($_GET);
                } catch (DomainException $error) {
                    http_response_code(422);
                    $periodError = $error->getMessage();
                    $period = Period::fromQuery([]);
                }
                $overview = (new Overview($database))->load($period);
            }
            require dirname(__DIR__) . '/Views/admin/home.php';
        }
    }

    private function feature(string $route, string $method): void
    {
        $userId = AdminSession::userId();
        if ($userId === null) {
            self::redirect(self::LOGIN);
            return;
        }

        $database = Connection::open();
        $access = new AdminAccess($database);

        $about = str_starts_with($route, 'admin-a-propos');
        $requests = $route === 'admin-demandes';
        $permission = $requests ? 'requests.manage' : ($about ? 'about.manage' : 'catalog.manage');

        if (!$access->can($userId, 'admin.access') || !$access->can($userId, $permission)) {
            http_response_code(403);
            if ($method !== 'HEAD') {
                echo 'Accès à ce module refusé.';
            }
            return;
        }

        AdminSession::touch();
        if ($requests) {
            (new AdminRequestsController(new ClientRequests($database)))->dispatch(
                $method, $userId, $access->can($userId, 'catalog.manage'), $access->can($userId, 'about.manage'),
            );
        } elseif ($about) {
            (new AdminAboutController(new AboutContent($database)))->dispatch(
                $route, $method, $access->can($userId, 'catalog.manage'), $access->can($userId, 'requests.manage'),
            );
        } else {
            (new AdminCatalogueController(new Catalogue($database)))->dispatch(
                $route, $method, $access->can($userId, 'about.manage'), $access->can($userId, 'requests.manage'),
            );
        }
    }

    private function logout(): void
    {
        $userId = AdminSession::userId();
        AdminSession::clear();

        if ($userId !== null) {
            error_log("Déconnexion administration : utilisateur {$userId}");
        }

        self::redirect(self::LOGIN);
    }

    private function loginForm(?string $message = null): void
    {
        $csrf = AdminSession::token();
        require dirname(__DIR__) . '/Views/admin/login.php';
    }

    private static function redirect(string $url): void
    {
        header('Location: ' . $url, true, 303);
    }
}
