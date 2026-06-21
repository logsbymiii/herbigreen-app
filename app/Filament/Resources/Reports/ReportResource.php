<?php

namespace App\Filament\Resources\Reports;

use App\Filament\Resources\Reports\Pages\CreateReport;
use App\Filament\Resources\Reports\Pages\EditReport;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Reports\Tables\ReportsTable;
use App\Models\Report;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Laporan Harian';

    protected static ?string $pluralModelLabel = 'Laporan Harian';
    
    protected static string|\UnitEnum|null $navigationGroup = 'AI & Analitik';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->label('Karyawan')
                    ->searchable()
                    ->preload()
                    ->required(),

                \Filament\Forms\Components\Select::make('type')
                    ->options([
                        'harian' => 'Laporan Harian',
                        'mingguan' => 'Laporan Mingguan',
                        'insidental' => 'Insidental (Kejadian Khusus)',
                    ])
                    ->label('Tipe Laporan')
                    ->required(),

                \Filament\Forms\Components\Textarea::make('content')
                    ->label('Isi Laporan')
                    ->columnSpanFull()
                    ->required(),

                \Filament\Forms\Components\FileUpload::make('media_path')
                    ->label('Upload Foto / Bukti')
                    ->image()
                    ->directory('laporan-herbigreen')
                    ->columnSpanFull(),

                \Filament\Forms\Components\DateTimePicker::make('reported_at')
                    ->label('Tanggal Laporan')
                    ->default(now())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'create' => CreateReport::route('/create'),
            'edit' => EditReport::route('/{record}/edit'),
        ];
    }


}
