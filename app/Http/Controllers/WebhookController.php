<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MessageClassifier;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
  public function receive(Request $request, MessageClassifier $classifier)
    {
        $sender = $request->input('sender');
        $message = $request->input('message');
        $urlFile = $request->input('url');

        //file type
       $type = $classifier->classify($sender, $message, !empty($urlFile));
       $reportContent = preg_replace('/(#lapor|\/lapor)\s*/i', '', $message);

       //biar muncul di laravel.log
        Log::info("WA MASUK PAK! Tipe: $type | Dari: $sender | Isi Laporan: $reportContent");

        return response()->json([
            'status' => true
        ]);
    }
}
