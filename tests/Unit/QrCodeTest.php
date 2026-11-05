<?php

declare(strict_types=1);

use App\Support\QrCode;

test('it returns an svg data uri', function () {
    $dataUri = QrCode::svgDataUri('https://example.test/guild/m/sample');
    $svg = base64_decode(
        substr($dataUri, strlen('data:image/svg+xml;base64,')),
        true,
    );

    expect($dataUri)->toStartWith('data:image/svg+xml;base64,');
    expect($svg)->toBeString()->toContain('<svg');
});
