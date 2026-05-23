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
                    ->required(),

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
            ]);
    }
}
