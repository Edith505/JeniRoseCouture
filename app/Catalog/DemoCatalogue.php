<?php

declare(strict_types=1);

namespace App\Catalog;

final class DemoCatalogue
{
    public static function services(): array
    {
        return [
            self::service('creation-sur-mesure', 'Création sur mesure', 'Une pièce conçue à partir de vos idées et de vos mesures.',
                "Robes sur mesure\nVêtements de soirée\nPièces uniques", '150.00', 'creation', 'quote', 10),
            self::service('retouches', 'Retouches de vêtements', 'Ourlets, ajustements et transformations pour un tombé qui vous convient.',
                "Ourlets\nAjustements de taille\nTransformations", '15.00', 'retouches', 'appointment', 20),
            self::service('repassage', 'Repassage professionnel', 'Un soin adapté aux chemises, robes et textiles délicats.',
                "Chemises\nRobes\nTextiles délicats", '5.00', 'repassage', 'appointment', 30),
            self::service('reparation', 'Boutonnage et réparation', 'Boutons, fermetures et coutures à reprendre pour prolonger la vie de vos vêtements.',
                "Remplacement de boutons\nFermetures éclair\nCoutures à réparer", '8.00', 'reparation', 'quote', 40),
            self::service('linge-de-maison', 'Rideaux et linge de maison', 'Ourlets, coussins et ajustements pour les textiles de votre intérieur.',
                "Ourlets de rideaux\nCoussins\nRetouches de linge de maison", '25.00', 'linge', 'information', 50),
            self::service('conseils-couture', 'Conseils en couture', 'Des séances pour prendre en main votre machine ou avancer sur un projet.',
                "Initiation à la machine\nTechniques de retouches\nProjets personnalisés", '30.00', 'conseils', 'appointment', 60, 'from_hourly'),
        ];
    }

    private static function service(
        string $slug,
        string $name,
        string $description,
        string $examples,
        string $price,
        string $image,
        string $action,
        int $order,
        string $type = 'from',
    ): array {
        return [
            'id' => null,
            'slug' => $slug,
            'name' => $name,
            'category_name' => $name,
            'description' => $description,
            'examples' => $examples,
            'conditions' => '',
            'price_type' => $type,
            'price_amount' => $price,
            'price_is_demo' => true,
            'duration_minutes' => null,
            'service_mode' => 'confirm',
            'action_intent' => $action,
            'asset_name' => 'service-' . $image . '.webp',
            'has_upload' => false,
            'sort_order' => $order,
        ];
    }
}
