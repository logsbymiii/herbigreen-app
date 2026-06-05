<?php

namespace App\Services;

use App\Contracts\ConversationStateInterface;
use App\Models\ConversationState;

class DatabaseConversationState implements ConversationStateInterface
{
    public function getCurrentStep(int | string $identifier): ?string
    {
        $state = ConversationState::where('identifier', (string) $identifier)
            ->where('provider', 'telegram')
            ->first();
        return $state?->current_step;
    }

    public function setCurrentStep(int | string $identifier, string $step, array $data = []): void
    {
        $state = ConversationState::firstOrNew([
            'identifier' => (string) $identifier,
            'provider' => 'telegram',
        ]);
        $state->current_step = $step;
        
        if (!empty($data)) {
            $state->temp_data = array_merge($state->temp_data ?? [], $data);
        }
        
        $state->save();
    }

    public function getTempData(int | string $identifier): array
    {
        $state = ConversationState::where('identifier', (string) $identifier)
            ->where('provider', 'telegram')
            ->first();
        return $state?->temp_data ?? [];
    }

    public function updateTempData(int | string $identifier, array $data): void
    {
        $state = ConversationState::where('identifier', (string) $identifier)
            ->where('provider', 'telegram')
            ->first();
        if ($state) {
            $state->update(['temp_data' => array_merge($state->temp_data ?? [], $data)]);
        }
    }

    public function clearState(int | string $identifier): void
    {
        ConversationState::where('identifier', (string) $identifier)
            ->where('provider', 'telegram')
            ->delete();
    }

    public function hasActiveConversation(int | string $identifier): bool
    {
        return ConversationState::where('identifier', (string) $identifier)
            ->where('provider', 'telegram')
            ->exists();
    }
}
