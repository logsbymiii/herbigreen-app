<?php

namespace App\Filament\Resources\Attendances;

use App\Filament\Resources\Attendances\Pages\CreateAttendance;
use App\Filament\Resources\Attendances\Pages\EditAttendance;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\Attendances\Schemas\AttendanceForm;
use App\Filament\Resources\Attendances\Tables\AttendancesTable;
use App\Models\Attendance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    // 1. Ganti icon kotak numpuk bawaan jadi jam
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static string|\UnitEnum|null $navigationGroup = 'HR & Karyawan';

    // 2. Tambahin label bahasa Indonesianya
    protected static ?string $modelLabel = 'Kehadiran';
    protected static ?string $pluralModelLabel = 'Kehadiran';

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        if ($record && $record->employee) {
            return "Absensi {$record->employee->name}";
        }
        return 'Laporan Absensi';
    }

    public static function form(Schema $schema): Schema
    {
        return AttendanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
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
            'index' => ListAttendances::route('/'),
            'create' => CreateAttendance::route('/create'),
            'edit' => EditAttendance::route('/{record}/edit'),
        ];
    }
}
