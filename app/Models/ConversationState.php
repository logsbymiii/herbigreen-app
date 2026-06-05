<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationState extends Model
{
    protected $fillable = [
        'provider',
        'identifier',
        'current_step',
        'temp_data',
    ];

    protected $casts = [
        'temp_data' => 'array',
    ];

    public static function getOrCreate(string $provider, string | int $identifier)
    {
        return self::firstOrCreate(
            ['provider' => $provider, 'identifier' => (string) $identifier],
            ['current_step' => 'start', 'temp_data' => []]
        );
    }
}

