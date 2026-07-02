<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_id')
                    ->required()
                    ->numeric(),
                Select::make('type')
                    ->options(['sakit' => 'Sakit', 'izin' => 'Izin'])
                    ->required(),
                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('proof_path')
                    ->default(null),
                DatePicker::make('request_date')
                    ->required(),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('responded_at'),
            ]);
    }
}
