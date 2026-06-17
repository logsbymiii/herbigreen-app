<?php

namespace App\Filament\Resources\SmartDailyReports;

use App\Filament\Resources\SmartDailyReports\Pages\CreateSmartDailyReport;
use App\Filament\Resources\SmartDailyReports\Pages\EditSmartDailyReport;
use App\Filament\Resources\SmartDailyReports\Pages\ListSmartDailyReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SmartDailyReportResource extends Resource
{
    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Smart Daily Reports';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan AI';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return SmartDailyReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmartDailyReportsTable::configure($table);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Detail Karyawan')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('employee.name')->label('Nama'),
                        \Filament\Infolists\Components\TextEntry::make('employee.division.name')->label('Divisi'),
                        \Filament\Infolists\Components\TextEntry::make('report_date')->label('Tanggal Laporan')->date('d M Y'),
                    ])->columns(3),

                \Filament\Infolists\Components\Section::make('Laporan Mentah dari Karyawan')
                    ->description('Isi chat asli yang dikirim via bot')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('raw_report')
                            ->hiddenLabel()
                            ->markdown(),
                    ])->collapsible(),

                \Filament\Infolists\Components\Section::make('Hasil Analisa Gemini AI')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('ai_insight')
                            ->label('Insight / Kesimpulan')
                            ->color('primary')
                            ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                        \Filament\Infolists\Components\KeyValueEntry::make('extracted_metrics')
                            ->label('Metrik Tersaring')
                            ->keyLabel('Kunci (Metrik)')
                            ->valueLabel('Nilai'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\SmartDailyReportOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmartDailyReports::route('/'),
            'create' => CreateSmartDailyReport::route('/create'),
            'edit' => EditSmartDailyReport::route('/{record}/edit'),
        ];
    }
}
