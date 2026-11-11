<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as Contract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * After signing in from the company page, users land on the portal home page (/).
 * If they were sent to the login page from a panel URL, they go back to that URL instead.
 */
class LoginResponse implements Contract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->intended(route('home'));
    }
}
