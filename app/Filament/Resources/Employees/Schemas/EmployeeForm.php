<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('division_id')
                    ->label('Divisi / Departemen')
                    ->relationship('division', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive(),

                Select::make('shift')
                    ->label('Shift (Khusus Host Live)')
                    ->options([
                        'pagi' => 'Shift Pagi (1 Sesi)',
                        'malam' => 'Shift Malam (1 Sesi)',
                        'full' => 'Shift Full (Pagi & Malam)',
                    ])
                    ->hidden(fn (\Filament\Forms\Get $get) => 
                        \App\Models\Division::find($get('division_id'))?->name !== 'Host Live'
                    ),

                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->placeholder('Masukkan nama karyawan')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Nomor WhatsApp')
                    ->tel()
                    ->placeholder('Contoh: 081234567890 (Tanpa spasi/strip)')
                    ->required()
                    ->maxLength(255),

                Toggle::make('is_active')
                    ->label('Status Karyawan Aktif')
                    ->default(true)
                    ->inline(false)
                    ->required(),
                    
                Select::make('role')
                    ->label('Role Karyawan')
                    ->options([
                        'user' => 'User Biasa',
                        'admin' => 'Admin (Bebas Lapor & Tes Bot)',
                    ])
                    ->default('user')
                    ->required(),
            ]);
    }
}
