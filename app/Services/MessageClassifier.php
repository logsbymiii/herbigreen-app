<?php

namespace App\Services;

class MessageClassifier
{
    public function classify($sender, $message, $hasMedia = false, $employeeDivision = null)
    {
        $lower = strtolower(trim($message));

        // Deteksi sapaan — halo, hai, p, selamat pagi, dll
        $greetings = ['halo', 'hai', 'hi', 'hey', 'hei', 'p', 'ping',
                      'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam',
                      'pagi', 'siang', 'sore', 'malam', 'assalamualaikum', 'ass', 'waalaikumsalam',
                      'om', 'permisi', 'hallo', 'hello', 'menu'];
        if (in_array($lower, $greetings) || preg_match('/^(halo|hai|hi|hey|hei|selamat)/i', $lower)) {
            return 'greeting';
        }

        // Deteksi absen — kata kunci sakit/izin/cuti
        if (preg_match('/^(izin|sakit)/', $lower)) {
            return 'attendance';
        }

        // Deteksi laporan harian via hashtag (alternatif selain /lapor command)
        if (str_contains($lower, '#lapor')) {
            return 'daily_report';
        }

        // Deteksi GMV — via command /gmv ATAU foto dari divisi Host Live
        $isHostLive = strtolower($employeeDivision ?? '') === 'host live';
        if (str_contains($lower, '/gmv') || ($hasMedia && $isHostLive)) {
            return 'gmv_report';
        }

        return 'general_chat';
    }
}
