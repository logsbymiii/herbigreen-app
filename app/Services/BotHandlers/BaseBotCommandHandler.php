<?php

namespace App\Services\BotHandlers;

use App\Contracts\BotCommandHandlerInterface;
use App\Contracts\ConversationStateInterface;
use App\Services\MessageProviderFactory;
use Illuminate\Support\Facades\Log;

abstract class BaseBotCommandHandler implements BotCommandHandlerInterface
{
    protected ConversationStateInterface $conversationState;
    protected string $provider;

    public function __construct(ConversationStateInterface $conversationState, string $provider)
    {
        $this->conversationState = $conversationState;
        $this->provider = $provider;
    }

    public function getCommands(): array
    {
        return [
            'start' => 'Tampilkan welcome message & menu',
            'daftar' => 'Registrasi employee baru',
            'bantuan' => 'Tampilkan bantuan',
        ];
    }

    public function isCommand(string $message): bool
    {
        return str_starts_with(trim($message), '/');
    }

    public function getCommandName(string $message): ?string
    {
        if (!$this->isCommand($message)) {
            return null;
        }

        preg_match('/^\/(\w+)/', $message, $matches);
        return $matches[1] ?? null;
    }

    protected function sendMessage(int | string $identifier, string $message): bool
    {
        $provider = MessageProviderFactory::create();
        return $provider->sendMessage((string) $identifier, $message);
    }

    protected function logConversation(int | string $identifier, string $step, string $message): void
    {
        Log::info("CONVERSATION: Provider={$this->provider}, Identifier={$identifier}, Step={$step}, Message={$message}");
    }
}
