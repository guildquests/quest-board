<?php

declare(strict_types=1);

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final class QrCode
{
    public static function svgDataUri(string $text): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(240, 4),
            new SvgImageBackEnd,
        );

        $svg = (new Writer($renderer))->writeString($text);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
