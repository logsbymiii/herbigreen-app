<?php

namespace App\Services\BotHandlers;

class FonnteBotCommandHandler extends BaseBotCommandHandler
{
    public function handle(int | string $identifier, string $message, array $rawUpdate): array
    {
        // TODO: Implementasi untuk Fonnte (sama seperti Telegram, hanya identifier-nya berbeda)
        // Identifier untuk Fonnte adalah phone number, bukan chat_id
        // Logic-nya bisa reuse 100% dari TelegramBotCommandHandler

        $command = $this->getCommandName($message);

        if ($command) {
            $this->logConversation($identifier, "command_{$command}", $message);
            // Handle commands (reuse dari parent atau TelegramBotCommandHandler)
        }

        return ['status' => true, 'message' => 'Fonnte handler - coming soon'];
    }
}
