<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Http\Middleware\SecurityHeaders;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('panel')
            ->login(Login::class)
            ->profile(EditProfile::class)
            ->brandName('أرشيف دال')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.png'))
            ->colors([
                // Explicit scales so buttons/links (shade 600) use the exact brand colours.
                'primary' => [
                    50 => '#edf6f1', 100 => '#d3e8de', 200 => '#a8cfbd', 300 => '#72ae94', 400 => '#3a8a6b',
                    500 => '#146a4b', 600 => '#00432D', 700 => '#003825', 800 => '#002d1e', 900 => '#002317', 950 => '#00150e',
                ],
                'gray' => Color::Stone,
                'info' => Color::hex('#2f6690'),
                'success' => [
                    50 => '#ebf8f2', 100 => '#cdeedf', 200 => '#9cdcc0', 300 => '#5fc39c', 400 => '#25a679',
                    500 => '#0a9666', 600 => '#03875A', 700 => '#036b48', 800 => '#05553a', 900 => '#064630', 950 => '#02281b',
                ],
                'warning' => Color::hex('#b7791f'),
                'danger' => Color::hex('#b4413a'),
            ])
            // Self-hosted fonts (public/fonts): Alexandria for text, Thmanyah for headings.
            ->font('Alexandria', url: '/fonts/fonts.css', provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(Width::ScreenTwoExtraLarge)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->spa()
            ->unsavedChangesAlerts()
            ->navigationGroups([
                NavigationGroup::make('لوحات المتابعة'),
                NavigationGroup::make('فريق الأنشطة'),
                NavigationGroup::make('فريق التحفيظ'),
                NavigationGroup::make('فريق التجويد'),
                NavigationGroup::make('فريق الشؤون الداخلية'),
                NavigationGroup::make('الإعدادات')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn () => Blade::render('<x-filament.sidebar-footer />'))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
