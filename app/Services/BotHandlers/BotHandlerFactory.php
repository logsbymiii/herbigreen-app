<?php

namespace App\Services\BotHandlers;

use App\Contracts\BotCommandHandlerInterface;
use App\Services\DatabaseConversationState;

class BotHandlerFactory
{
    public static function create(string $provider): BotCommandHandlerInterface
    {
        $conversationState = new DatabaseConversationState();

        return match ($provider) {
            'telegram' => new TelegramBotCommandHandler($conversationState, 'telegram'),
            'fonnte' => new FonnteBotCommandHandler($conversationState, 'fonnte'),
            default => throw new \InvalidArgumentException("Unknown bot provider: {$provider}"),
        };
    }
}
