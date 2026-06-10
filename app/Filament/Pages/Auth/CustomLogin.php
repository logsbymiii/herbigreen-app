<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class CustomLogin extends BaseLogin
{
    public $email = '';
    public $password = '';
    public $remember = false;

    protected string $view = 'filament.pages.auth.custom-login';

    public function getLayout(): string
    {
        return 'filament.pages.auth.custom-layout';
    }

    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
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

        return app(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class);
    }
}
