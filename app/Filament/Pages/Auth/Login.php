<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable|null
    {
        return 'مرحباً بعودتك';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'سجّل الدخول لأرشفة سجلات فريقك ومتابعة المؤشرات.';
    }
}
