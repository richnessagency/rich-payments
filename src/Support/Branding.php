<?php

declare(strict_types=1);

namespace Richness\RichPayments\Support;

final class Branding
{
    public static function siteName(): string
    {
        $name = config('rich-payments.views.site_name');

        return is_string($name) && $name !== '' ? $name : (string) config('app.name', 'Store');
    }

    public static function logoUrl(): ?string
    {
        $url = config('rich-payments.views.logo_url');

        return is_string($url) && $url !== '' ? $url : null;
    }

    public static function primaryColor(): string
    {
        return (string) config('rich-payments.views.primary_color', '#111827');
    }

    public static function accentColor(): string
    {
        return (string) config('rich-payments.views.accent_color', '#f97316');
    }

    public static function showPoweredBy(): bool
    {
        return (bool) config('rich-payments.views.show_powered_by', true);
    }
}
