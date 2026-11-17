<?php

declare(strict_types=1);

namespace App\Media;

use DomainException;

final class WebpUpload
{
    public static function fromRequest(?array $file): ?string
    {
        if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK || !is_string($file['tmp_name'])
            || !is_uploaded_file($file['tmp_name']) || (int) $file['size'] > 2 * 1024 * 1024
        ) {
            throw new DomainException('Sélectionnez une image de 2 Mio maximum.');
        }

        $info = @getimagesize($file['tmp_name']);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $info[0] < 1 || $info[1] < 1 || $info[0] > 4096 || $info[1] > 4096
            || $info[0] * $info[1] > 10000000
        ) {
            throw new DomainException('Utilisez une image JPEG, PNG ou WebP de dimensions raisonnables.');
        }

        $contents = file_get_contents($file['tmp_name']);
        $source = $contents === false ? false : @imagecreatefromstring($contents);
        if ($source === false) {
            throw new DomainException('Cette photographie ne peut pas être lue.');
        }

        $scale = min(1, 1200 / max($info[0], $info[1]));
        $resized = imagescale($source, max(1, (int) ($info[0] * $scale)), max(1, (int) ($info[1] * $scale)));
        if ($resized === false) {
            throw new DomainException('Cette photographie ne peut pas être redimensionnée.');
        }

        ob_start();
        $ok = imagewebp($resized, null, 84);
        $encoded = ob_get_clean();
        if (!$ok || $encoded === false || $encoded === '') {
            throw new DomainException('Cette photographie ne peut pas être enregistrée.');
        }

        return $encoded;
    }
}
