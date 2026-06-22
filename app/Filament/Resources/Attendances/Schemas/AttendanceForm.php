<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Karyawan')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->label('Tipe Kehadiran')
                    ->options(['hadir' => 'Hadir', 'wfh' => 'WFH', 'sakit' => 'Sakit', 'izin' => 'Izin', 'telat' => 'Terlambat', 'alpa' => 'Alpa'])
                    ->required(),
                Textarea::make('note')
                    ->label('Keterangan / Catatan')
                    ->default(null)
                    ->columnSpanFull(),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->required(),
                FileUpload::make('proof_path')
                    ->label('Bukti (Surat Sakit/Izin)')
                    ->image() // Opsional: kalau bukti wajib berupa foto/gambar
                    ->directory('attendance-proofs') // Nanti filenya kesimpen di folder storage/app/public/attendance-proofs
                    ->columnSpanFull(), // Biar kotaknya lebar full


            ]);
    }
}
