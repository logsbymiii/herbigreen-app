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
                TextInput::make('employee_id')
                    ->required()
                    ->numeric(),
                Textarea::make('raw_report')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('extracted_metrics')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('ai_insight')
                    ->default(null)
                    ->columnSpanFull(),
                DatePicker::make('report_date')
                    ->required(),
            ]);
    }
}
