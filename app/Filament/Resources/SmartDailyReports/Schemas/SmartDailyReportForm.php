<?php

namespace App\Filament\Resources\SmartDailyReports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SmartDailyReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->label('Nama Karyawan')
                    ->disabled()
                    ->required(),
                DatePicker::make('report_date')
                    ->label('Tanggal Laporan')
                    ->required(),
                Textarea::make('raw_report')
                    ->label('Laporan Mentah (Teks asli)')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('ai_insight')
                    ->label('Insight / Kesimpulan')
                    ->rows(3)
                    ->columnSpanFull(),
                \Filament\Forms\Components\KeyValue::make('extracted_metrics')
                    ->label('Metrik Tersaring')
                    ->keyLabel('Metrik')
                    ->valueLabel('Angka/Nilai')
                    ->columnSpanFull(),
            ]);
    }
}
