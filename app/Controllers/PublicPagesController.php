<?php

declare(strict_types=1);

namespace App\Controllers;

use App\About\AboutContent;
use App\Catalog\Catalogue;
use App\Database\Connection;

final class PublicPagesController
{
    private const PAGES = [
        '/a-propos' => ['À propos', 'Jeni Rose, créatrice passionnée', 'Création sur mesure, retouches et entretien textile.'],
        '/prestations' => ['Prestations', 'Nos prestations', 'De la première idée au dernier point, découvrez les possibilités pour vos vêtements et vos textiles.'],
        '/tarifs' => ['Tarifs', 'Tarifs indicatifs', 'Le prix dépend de la pièce, de la matière et du travail à réaliser.'],
        '/galerie' => ['Galerie', 'Regards sur la couture', 'Trois images d’ambiance autour des matières, du geste et du travail sur mannequin.'],
        '/contact' => ['Contact', 'Parlons de votre projet', 'Préparez les informations qui aideront à définir votre besoin.'],
        '/faq' => ['FAQ', 'Questions fréquentes', 'Quelques repères avant de faire ajuster, réparer ou créer une pièce.'],
        '/mentions-legales' => ['Mentions légales', 'Mentions légales', 'Version locale en cours de préparation.'],
        '/confidentialite' => ['Confidentialité', 'Confidentialité', 'Informations sur cette version de développement.'],
    ];

    public function dispatch(string $path, string $method): bool
    {
        if (preg_match('~^/prestations/([a-z0-9-]{1,140})$~D', $path, $match) === 1) {
            return $this->service($match[1], $method);
        }
        if (preg_match('~^/prestations-image/([1-9][0-9]{0,17})$~D', $path, $match) === 1) {
            return $this->image((int) $match[1], $method);
        }

        if (preg_match('~^/a-propos/photo/(portrait|[a-f0-9]{32})$~D', $path, $match) === 1) {
            return $this->aboutImage($match[1], $method);
        }

        $page = self::PAGES[$path] ?? null;
        if ($page === null) {
            return false;
        }

        if ($method !== 'GET' && $method !== 'HEAD') {
            http_response_code(405);
            header('Allow: GET, HEAD');
            return true;
        }

        if ($method === 'GET') {
            [$label, $heading, $intro] = $page;
            if ($path === '/prestations' || $path === '/tarifs') {
                $services = Catalogue::published();
            }
            $title = $label . ' | Jeni Rose Couture';
            $activePage = $path;
            $contentView = dirname(__DIR__) . '/Views/pages' . $path . '.php';
            if ($path === '/a-propos') {
                $content = $this->aboutPage();
                if ($content !== null) {
                    $heading = $content['headline'];
                    $contentView = dirname(__DIR__) . '/Views/partials/about-content.php';
                }
            }
            require dirname(__DIR__) . '/Views/partials/public-page.php';
        }

        return true;
    }

    private function aboutPage(): ?array
    {
        try {
            return (new AboutContent(Connection::open()))->page('published');
        } catch (\PDOException $error) {
            if (($error->errorInfo[1] ?? null) === 1146) {
                return null;
            }
            throw $error;
        }
    }

    private function aboutImage(string $key, string $method): bool
    {
        if ($method !== 'GET' && $method !== 'HEAD') {
            http_response_code(405);
            header('Allow: GET, HEAD');
            return true;
        }

        try {
            $image = (new AboutContent(Connection::open()))->picture('published', $key);
        } catch (\PDOException $error) {
            if (($error->errorInfo[1] ?? null) === 1146) {
                return false;
            }
            throw $error;
        }

        if ($image === null) {
            return false;
        }

        header('Content-Type: image/webp');
        header('Content-Length: ' . strlen($image));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-cache');
        if ($method === 'GET') {
            echo $image;
        }

        return true;
    }

    private function service(string $slug, string $method): bool
    {
        $service = Catalogue::published($slug)[0] ?? null;
        if ($service === null) {
            return false;
        }

        if ($method !== 'GET' && $method !== 'HEAD') {
            http_response_code(405);
            header('Allow: GET, HEAD');
            return true;
        }

        if ($method === 'GET') {
            $title = $service['name'] . ' | Jeni Rose Couture';
            $heading = $service['name'];
            $intro = $service['description'];
            $activePage = '/prestations';
            $contentView = dirname(__DIR__) . '/Views/pages/detail-prestation.php';
            require dirname(__DIR__) . '/Views/partials/public-page.php';
        }

        return true;
    }

    private function image(int $id, string $method): bool
    {
        if ($method !== 'GET' && $method !== 'HEAD') {
            http_response_code(405);
            header('Allow: GET, HEAD');
            return true;
        }

        $image = (new Catalogue(Connection::open()))->publicImage($id);
        if ($image === null) {
            return false;
        }

        header('Content-Type: image/webp');
        header('Content-Length: ' . strlen($image));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-cache');
        if ($method === 'GET') {
            echo $image;
        }

        return true;
    }
}
