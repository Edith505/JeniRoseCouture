<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Catalog\Catalogue;

final class HomeController
{
    public function index(): void
    {
        $services = Catalogue::published();
        require dirname(__DIR__) . '/Views/home.php';
    }
}
