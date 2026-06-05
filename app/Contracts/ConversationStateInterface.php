<?php

namespace App\Contracts;

interface ConversationStateInterface
{
    /**
     * Get current conversation step
     */
    public function getCurrentStep(int | string $identifier): ?string;

    /**
     * Set current step
     */
    public function setCurrentStep(int | string $identifier, string $step, array $data = []): void;

    /**
     * Get temporary data from conversation
     */
    public function getTempData(int | string $identifier): array;

    /**
     * Update temporary data
     */
    public function updateTempData(int | string $identifier, array $data): void;

    /**
     * Clear conversation state
     */
    public function clearState(int | string $identifier): void;

    /**
     * Check if conversation exists
     */
    public function hasActiveConversation(int | string $identifier): bool;
}
