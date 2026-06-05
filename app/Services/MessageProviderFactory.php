<?php

namespace App\Services;

use App\Contracts\MessageProviderInterface;
use Illuminate\Support\Facades\Log;

class MessageProviderFactory
{
    public static function create(): MessageProviderInterface
    {
        $provider = env('MESSAGE_PROVIDER', 'fonnte');

        return match ($provider) {
            'telegram' => new TelegramService(),
            'fonnte' => new FonnteService(),
            default => throw new \InvalidArgumentException("Unknown message provider: {$provider}"),
        };
    }
}
