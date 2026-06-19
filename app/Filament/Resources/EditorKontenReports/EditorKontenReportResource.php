<?php

namespace App\Filament\Resources\EditorKontenReports;

use App\Filament\Resources\EditorKontenReports\Pages\CreateEditorKontenReport;
use App\Filament\Resources\EditorKontenReports\Pages\EditEditorKontenReport;
use App\Filament\Resources\EditorKontenReports\Pages\ListEditorKontenReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EditorKontenReportResource extends Resource
{
    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Laporan Editor Konten';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan AI';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('employee.division', function ($query) {
            $query->where('name', 'Editor Konten');
        });
    }

    public static function form(Schema $schema): Schema
    {
        return SmartDailyReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmartDailyReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEditorKontenReports::route('/'),
            'create' => CreateEditorKontenReport::route('/create'),
            'edit' => EditEditorKontenReport::route('/{record}/edit'),
        ];
    }
}