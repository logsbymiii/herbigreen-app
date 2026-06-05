<?php

namespace App\Contracts;

interface MessageProviderInterface
{
    public function sendMessage(string $target, string $message): bool;

    public function getProviderName(): string;
}
