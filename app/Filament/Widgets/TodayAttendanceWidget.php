<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TodayAttendanceWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Daftar Karyawan Izin/Sakit Hari Ini';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Attendance::query()
                    ->with('employee')
                    ->whereDate('created_at', Carbon::today())
                    ->whereIn('type', ['sakit', 'izin'])
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.division.name')
                    ->label('Divisi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sakit' => 'danger',
                        'izin' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('note')
                    ->label('Catatan Tambahan')
                    ->wrap()
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Waktu Lapor')
                    ->time('H:i')
                    ->sortable(),
            ]);
    }
}
