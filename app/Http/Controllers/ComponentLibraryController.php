<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\QrCode;
use Inertia\Inertia;
use Inertia\Response;

class ComponentLibraryController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('docs/library', [
            'sample_qr' => QrCode::svgDataUri(
                url('/guild/m/0123456789abcdef01234567'),
            ),
        ]);
    }
}
