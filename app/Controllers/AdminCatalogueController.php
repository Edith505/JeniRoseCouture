<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AdminSession;
use App\Catalog\Catalogue;
use App\Catalog\CatalogueInput;
use App\Media\WebpUpload;
use DomainException;
use PDOException;

final class AdminCatalogueController
{
    public function __construct(private readonly Catalogue $catalogue)
    {
    }

    public function dispatch(string $route, string $method, array $navigation): void
    {
        [$catalogueNavigation, $aboutNavigation, $requestNavigation, $salesNavigation, $financeNavigation] = $navigation;
        $catalogueNavigation = true;
        $activeAdminPage = 'catalogue';
        $csrf = AdminSession::token();

        if ($route === 'admin-catalogue') {
            if ($method === 'GET') {
                $title = 'Catalogue | Jeni Rose Couture';
                $services = $this->catalogue->entries();
                $categories = $this->catalogue->categories();
                $notice = ($_GET['notice'] ?? '') === 'saved' ? 'Les changements ont été enregistrés.' : null;
                $contentView = dirname(__DIR__) . '/Views/admin/catalogue.php';
                require dirname(__DIR__) . '/Views/partials/admin-shell.php';
            }
            return;
        }

        $categoryForm = $route === 'admin-categorie';
        $id = null;
        if (isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                $this->missing($method);
                return;
            }
        }

        $item = $id === null ? [] : ($categoryForm ? $this->catalogue->category($id) : $this->catalogue->service($id));
        if ($item === null || (!$categoryForm && !empty($item['archived_at']))) {
            $this->missing($method);
            return;
        }
        if ($method === 'HEAD') {
            return;
        }

        $message = null;
        if ($method === 'POST') {
            try {
                if ($categoryForm) {
                    $this->catalogue->saveCategory($id, CatalogueInput::category($_POST));
                } elseif (($_POST['intent'] ?? '') === 'archive' && $id !== null) {
                    $this->catalogue->archive($id);
                } else {
                    $fields = CatalogueInput::service($_POST);
                    if ($this->catalogue->category($fields['category_id']) === null) {
                        throw new DomainException('Choisissez une catégorie existante.');
                    }
                    $image = WebpUpload::fromRequest($_FILES['photo'] ?? null);
                    $remove = ($_POST['remove_image'] ?? '') === '1';
                    if ($remove && $image !== null) {
                        throw new DomainException('Choisissez entre supprimer la photo et la remplacer.');
                    }
                    $this->catalogue->saveService($id, $fields, $image, $remove);
                }

                header('Location: /index.php?route=admin-catalogue&notice=saved', true, 303);
                return;
            } catch (DomainException $error) {
                $message = $error->getMessage();
                http_response_code(422);
            } catch (PDOException $error) {
                if (($error->errorInfo[1] ?? null) !== 1062) {
                    throw $error;
                }
                $message = 'Ce nom produit une adresse Web déjà utilisée.';
                http_response_code(409);
            }

            $item = array_replace($item, array_intersect_key(
                array_filter($_POST, 'is_string'),
                array_flip(['name', 'description', 'conditions', 'examples', 'price_type', 'price_amount',
                    'category_id', 'duration_minutes', 'service_mode', 'action_intent', 'sort_order']),
            ), [
                'is_enabled' => ($_POST['is_enabled'] ?? '') === '1',
                'price_is_demo' => ($_POST['price_is_demo'] ?? '') === '1',
            ]);
        }

        $categories = $categoryForm ? [] : $this->catalogue->categories();
        $heading = ($id === null ? 'Ajouter' : 'Modifier') . ($categoryForm ? ' une catégorie' : ' une prestation');
        $title = $heading . ' | Jeni Rose Couture';
        $contentView = dirname(__DIR__) . '/Views/admin/catalogue-form.php';
        require dirname(__DIR__) . '/Views/partials/admin-shell.php';
    }

    private function missing(string $method): void
    {
        http_response_code(404);
        if ($method !== 'HEAD') {
            echo 'Élément du catalogue introuvable.';
        }
    }
}
