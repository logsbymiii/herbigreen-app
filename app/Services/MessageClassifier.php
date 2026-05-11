<?php

namespace App\Services;

class MessageClassifier
{
    protected $hostLiveNumbers= [
        '6285606178752',
        '628987654321',
    ];

    public function classify($sender, $message, $hasMedia= false)
    {
        $message =strtolower($message);

        if(preg_match('/(izin|sakit|cuti)/', $message)) //chat classifier
            return 'attendance';

        if(str_contains($message, '#lapor') || str_contains($message, '/lapor')) //chat classifier
        {
            return 'daily_report';
        }

       if ($hasMedia && in_array($sender, $this->hostLiveNumbers)) //chat Classifier
        {
        return 'gmv_report';
        }


        return 'general_chat'; //chat classifier
    }

}
