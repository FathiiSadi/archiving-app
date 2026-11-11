<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Reached from the Archiving App company page, so it carries no organisation's logo or name.
 */
class Login extends BaseLogin
{
    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'تسجيل الدخول';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'ادخل بريدك الإلكتروني وكلمة المرور للوصول إلى نظام جهتك.';
    }
}
