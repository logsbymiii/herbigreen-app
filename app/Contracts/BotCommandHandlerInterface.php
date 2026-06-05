<?php

namespace App\Contracts;

interface BotCommandHandlerInterface
{
    /**
     * Handle incoming message/command
     */
    public function handle(int | string $identifier, string $message, array $rawUpdate): array;

    /**
     * Get list of available commands
     */
    public function getCommands(): array;

    /**
     * Check if message is a command
     */
    public function isCommand(string $message): bool;

    /**
     * Get command name from message
     */
    public function getCommandName(string $message): ?string;
}
