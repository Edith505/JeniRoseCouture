<?php

declare(strict_types=1);

namespace App\Controllers;

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
            $title = $label . ' | Jeni Rose Couture';
            $activePage = $path;
            $contentView = dirname(__DIR__) . '/Views/pages' . $path . '.php';
            require dirname(__DIR__) . '/Views/partials/public-page.php';
        }

        return true;
    }
}
