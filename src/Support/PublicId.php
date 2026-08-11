<?php

declare(strict_types=1);

namespace Richness\RichPayments\Support;

use Illuminate\Support\Str;

final class PublicId
{
    public static function generate(): string
    {
        return (string) Str::ulid();
    }
}
