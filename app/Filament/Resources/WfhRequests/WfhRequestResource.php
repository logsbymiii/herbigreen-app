<?php

namespace App\Filament\Resources\WfhRequests;

use App\Filament\Resources\WfhRequests\Pages\CreateWfhRequest;
use App\Filament\Resources\WfhRequests\Pages\EditWfhRequest;
use App\Filament\Resources\WfhRequests\Pages\ListWfhRequests;
use App\Filament\Resources\WfhRequests\Schemas\WfhRequestForm;
use App\Filament\Resources\WfhRequests\Tables\WfhRequestsTable;
use App\Models\WfhRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WfhRequestResource extends Resource
{
    protected static ?string $model = WfhRequest::class;

    protected static ?string $modelLabel = 'Izin WFH / Remote';
    protected static ?string $pluralModelLabel = 'Izin WFH / Remote';
    protected static ?string $navigationGroup = 'Manajemen SDM';
    protected static ?string $navigationLabel = 'Izin WFH / Remote';
    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    public static function form(Schema $schema): Schema
    {
        return WfhRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WfhRequestsTable::configure($table);
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
            'index' => ListWfhRequests::route('/'),
            'create' => CreateWfhRequest::route('/create'),
            'edit' => EditWfhRequest::route('/{record}/edit'),
        ];
    }
}
