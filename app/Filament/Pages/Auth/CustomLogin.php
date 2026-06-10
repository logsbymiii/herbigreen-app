<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class CustomLogin extends BaseLogin
{
    public $email = '';
    public $password = '';
    public $remember = false;

    protected static string $view = 'filament.pages.auth.custom-login';

    public function getLayout(): string
    {
        return 'filament.pages.auth.custom-layout';
    }

    public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! filament()->auth()->attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember)) {
            $this->addError('email', __('filament-panels::pages/auth/login.messages.failed'));
            return null;
        }

        session()->regenerate();

        return app(\Filament\Http\Responses\Auth\Contracts\LoginResponse::class);
    }
}
