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
                \Filament\Forms\Components\Section::make('Info Laporan')
                    ->columns(2)
                    ->schema([
                        \Filament\Forms\Components\Select::make('employee_id')
                            ->relationship('employee', 'name')
                            ->label('Nama Karyawan')
                            ->disabled()
                            ->required(),
                        DatePicker::make('report_date')
                            ->label('Tanggal Laporan')
                            ->required(),
                    ]),

                \Filament\Forms\Components\Section::make('Laporan Mentah')
                    ->description('Teks asli yang dikirim oleh karyawan via Bot')
                    ->schema([
                        Textarea::make('raw_report')
                            ->hiddenLabel()
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                \Filament\Forms\Components\Section::make('Hasil Analisa Gemini AI')
                    ->schema([
                        Textarea::make('ai_insight')
                            ->label('Insight / Kesimpulan')
                            ->rows(3)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\KeyValue::make('extracted_metrics')
                            ->label('Metrik Tersaring')
                            ->keyLabel('Metrik')
                            ->valueLabel('Angka/Nilai')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
