<?php

namespace App\Filament\Resources\CrmReports;

use App\Filament\Resources\CrmReports\Pages\CreateCrmReport;
use App\Filament\Resources\CrmReports\Pages\EditCrmReport;
use App\Filament\Resources\CrmReports\Pages\ListCrmReports;
use App\Filament\Resources\SmartDailyReports\Schemas\SmartDailyReportForm;
use App\Filament\Resources\SmartDailyReports\Tables\SmartDailyReportsTable;
use App\Models\SmartDailyReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CrmReportResource extends Resource
{
    protected static ?string $model = SmartDailyReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Laporan CRM';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan AI';

    protected static ?int $navigationSort = 5;

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        if ($record && $record->employee) {
            return "CRM {$record->employee->name}";
        }
        return 'Laporan CRM';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('employee.division', function ($query) {
            $query->where('name', 'Admin CRM');
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmReports::route('/'),
            'create' => CreateCrmReport::route('/create'),
            'edit' => EditCrmReport::route('/{record}/edit'),
        ];
    }

}
