<?php

declare(strict_types=1);

namespace App\Enums;

enum Category: string
{
    case Research = 'research';
    case Tooling = 'tooling';
    case Docs = 'docs';
    case Experiment = 'experiment';

    public function label(): string
    {
        return match ($this) {
            self::Research => 'Research',
            self::Tooling => 'Tooling',
            self::Docs => 'Docs',
            self::Experiment => 'Experiment',
        };
    }
}
