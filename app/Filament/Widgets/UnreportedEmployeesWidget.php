<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class UnreportedEmployeesWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Wall of Shame: Belum Lapor Hari Ini';

    public function table(Table $table): Table
    {
        $today = Carbon::today();

        return $table
            ->query(
                Employee::query()
                    ->where('is_active', true)
                    ->whereDoesntHave('reports', function (Builder $query) use ($today) {
                        $query->whereDate('created_at', $today);
                    })
                    ->whereDoesntHave('attendances', function (Builder $query) use ($today) {
                        $query->whereDate('created_at', $today)
                              ->whereIn('type', ['sakit', 'izin']);
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('division.name')
                    ->label('Divisi')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->default('Belum Lapor')
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-x-circle'),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Semua Karyawan Sudah Lapor 🎉')
            ->emptyStateDescription('Tidak ada karyawan yang mangkir laporan hari ini.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated([5, 10, 25]);
    }
}
