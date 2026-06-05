<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Attendance;
use App\Services\MessageProviderFactory;
use Carbon\Carbon;

class SendMorningException extends Command
{
    protected $signature = 'app:send-morning-exception';
    protected $description = 'Kirim daftar karyawan sakit/cuti pagi hari';

    public function handle()
    {
        $today = Carbon::today();
        $exceptions = Attendance::whereDate('date', $today)
            ->whereIn('type', ['sakit', 'cuti'])
            ->with('employee')
            ->get();

        if ($exceptions->isEmpty()) {
            $this->info('✅ Semua karyawan sehat hari ini!');
            return;
        }

        $exceptionList = $exceptions->map(function ($exc) {
            $type = $exc->type === 'sakit' ? '🤒' : '📅';
            return "{$type} {$exc->employee->name} - {$exc->type}";
        })->implode("\n");

        $tanggal = $today->format('d M Y');
        $pesan = "📋 *Laporan Pagi - {$tanggal}*\n\n"
               . "Karyawan dengan pengecualian:\n"
               . $exceptionList;

        $adminPhone = env('ADMIN_PHONE');

        if (!$adminPhone) {
            $this->error("❌ ADMIN_PHONE belum di-set di .env!");
            return;
        }

        $provider = MessageProviderFactory::create();
        if ($provider->sendMessage($adminPhone, $pesan)) {
            $this->info("✅ Berhasil kirim laporan pagi ke Bos.");
        } else {
            $this->error("❌ Gagal kirim laporan pagi ke Bos.");
        }
    }
}
