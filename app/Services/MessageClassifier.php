<?php

namespace App\Services;

class MessageClassifier
{
    protected $hostLiveNumbers = [
        '6285606178752',
        '628987654321',
    ];

    public function classify($sender, $message, $hasMedia = false)
    {
        $message = strtolower($message);

        if (preg_match('/(izin|sakit|cuti)/', $message)) {
            return 'attendance';
        }

        if (str_contains($message, '#lapor') || str_contains($message, '/lapor')) {
            return 'daily_report';
        }

        if (str_contains($message, '/gmv') || ($hasMedia && in_array($sender, $this->hostLiveNumbers))) {
            return 'gmv_report';
        }

        return 'general_chat';
    }
}

