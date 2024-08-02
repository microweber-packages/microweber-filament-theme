<?php

namespace MicroweberPackages\MicroweberFilamentTheme;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Theme;
use Filament\Support\Color;
use Filament\Support\Facades\FilamentAsset;

class MicroweberFilamentTheme implements Plugin
{
    public function getId(): string
    {
        return 'microweber-filament-theme';
    }

    public function register(Panel $panel): void
    {
        FilamentAsset::register([
            Theme::make('microweber-filament-theme', __DIR__ . '/../resources/dist/microweber-filament-theme.css'),
        ]);

        $panel
            ->font('DM Sans')
//            ->primaryColor(Color::Amber)
//            ->secondaryColor(Color::Gray)
//            ->warningColor(Color::Amber)
//            ->dangerColor(Color::Rose)
//            ->successColor(Color::Green)
//            ->grayColor(Color::Gray)
            ->theme('microweber-filament-theme');
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
