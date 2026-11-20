<?php

declare(strict_types=1);

namespace App\Controllers;

use App\About\AboutContent;
use App\About\AboutInput;
use App\Auth\AdminSession;
use App\Media\WebpUpload;
use DomainException;

final class AdminAboutController
{
    private const MAIN = '/index.php?route=admin-a-propos';

    public function __construct(private readonly AboutContent $about)
    {
    }

    public function dispatch(string $route, string $method, array $navigation): void
    {
        [$catalogueNavigation, $aboutNavigation, $requestNavigation, $salesNavigation, $financeNavigation] = $navigation;
        if ($route === 'admin-a-propos-image') {
            $this->image($method);
            return;
        }

        $aboutNavigation = true;
        $activeAdminPage = 'about';
        $csrf = AdminSession::token();
        $content = $this->about->page('draft', true)
            ?? AboutContent::defaults() + ['is_demo' => false, 'has_portrait' => false, 'entries' => []];
        $notice = match ($_GET['notice'] ?? '') {
            'saved' => 'Brouillon enregistré.',
            'published' => 'Page À propos publiée.',
            default => null,
        };
        $message = null;

        if ($route === 'admin-a-propos-apercu') {
            if ($method === 'GET') {
                $title = 'Aperçu À propos | Jeni Rose Couture';
                $contentView = dirname(__DIR__) . '/Views/admin/about-preview.php';
                require dirname(__DIR__) . '/Views/partials/admin-shell.php';
            }
            return;
        }

        if ($route === 'admin-a-propos-entree') {
            $key = $_GET['key'] ?? null;
            if ($key !== null && (!is_string($key) || !preg_match('/^[a-f0-9]{32}$/D', $key))) {
                $this->notFound($method);
                return;
            }

            $entries = array_column($content['entries'], null, 'entry_key');
            $item = $key === null ? [] : ($entries[$key] ?? null);
            $kind = $item['kind'] ?? $_GET['kind'] ?? '';
            if (!is_string($kind) || !isset(AboutContent::KINDS[$kind]) || $item === null) {
                $this->notFound($method);
                return;
            }
            if ($method === 'HEAD') {
                return;
            }

            if ($method === 'POST') {
                try {
                    $intent = $_POST['intent'] ?? 'save';
                    if (($intent === 'archive' || $intent === 'restore') && $key !== null) {
                        $this->about->setArchive($key, $intent === 'archive');
                    } elseif (empty($item['archived'])) {
                        [$image, $remove] = self::picture();
                        $fields = AboutInput::entry($_POST, $kind);
                        $this->about->saveEntry($key, $fields, $image, $remove);
                    } else {
                        throw new DomainException('Restaurez cette entrée pour la modifier.');
                    }
                    self::redirect('saved');
                    return;
                } catch (DomainException $error) {
                    $message = $error->getMessage();
                    http_response_code(422);
                    $item = array_replace($item, self::posted(['title', 'organisation', 'period', 'description',
                        'responsibilities', 'competencies', 'sort_order']), [
                        'visible' => ($_POST['visible'] ?? '') === '1',
                        'is_demo' => ($_POST['is_demo'] ?? '') === '1',
                    ]);
                }
            }

            $title = 'Contenu À propos | Jeni Rose Couture';
            $contentView = dirname(__DIR__) . '/Views/admin/about-entry.php';
            require dirname(__DIR__) . '/Views/partials/admin-shell.php';
            return;
        }

        if ($method === 'HEAD') {
            return;
        }

        if ($method === 'POST') {
            try {
                if (($_POST['intent'] ?? '') === 'publish') {
                    $this->about->publish();
                    self::redirect('published');
                } else {
                    [$portrait, $remove] = self::picture();
                    $profile = AboutInput::profile($_POST);
                    $this->about->saveProfile($profile, ($_POST['is_demo'] ?? '') === '1', $portrait, $remove);
                    self::redirect('saved');
                }
                return;
            } catch (DomainException $error) {
                $message = $error->getMessage();
                http_response_code(422);
                if (($_POST['intent'] ?? '') !== 'publish') {
                    $content = array_replace($content, self::posted(['display_name', 'headline', 'story', 'quote']), [
                        'is_demo' => ($_POST['is_demo'] ?? '') === '1',
                    ]);
                }
            }
        }

        $title = 'Modifier la page À propos | Jeni Rose Couture';
        $contentView = dirname(__DIR__) . '/Views/admin/about-editor.php';
        require dirname(__DIR__) . '/Views/partials/admin-shell.php';
    }

    private function image(string $method): void
    {
        $key = $_GET['key'] ?? '';
        if (!is_string($key) || !preg_match('/^(portrait|[a-f0-9]{32})$/D', $key)) {
            $this->notFound($method);
            return;
        }

        $image = $this->about->picture('draft', $key, true);
        if ($image === null) {
            $this->notFound($method);
            return;
        }

        header('Content-Type: image/webp');
        header('Content-Length: ' . strlen($image));
        header('X-Content-Type-Options: nosniff');
        if ($method === 'GET') {
            echo $image;
        }
    }

    private static function picture(): array
    {
        $image = WebpUpload::fromRequest($_FILES['photo'] ?? null);
        $remove = ($_POST['remove_image'] ?? '') === '1';
        if ($remove && $image !== null) {
            throw new DomainException('Choisissez entre supprimer la photo et la remplacer.');
        }

        return [$image, $remove];
    }

    private static function posted(array $keys): array
    {
        return array_intersect_key(array_filter($_POST, 'is_string'), array_flip($keys));
    }

    private static function redirect(string $notice): void
    {
        header('Location: ' . self::MAIN . '&notice=' . $notice, true, 303);
    }

    private function notFound(string $method): void
    {
        http_response_code(404);
        if ($method !== 'HEAD') {
            echo 'Contenu introuvable.';
        }
    }
}
