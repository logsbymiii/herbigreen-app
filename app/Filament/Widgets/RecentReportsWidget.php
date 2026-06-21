<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class RecentReportsWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Laporan Terbaru (Live Feed)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Report::query()->with('employee')->latest('created_at')->limit(5)
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Nama Karyawan')
                    ->searchable(),
                TextColumn::make('employee.division.name')
                    ->label('Divisi'),
                TextColumn::make('content')
                    ->label('Isi Laporan')
                    ->wrap()
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Waktu Masuk')
                    ->since() // Shows "5 mins ago"
                    ->sortable(),
            ])
            ->paginated(false); // Only show latest 5, no pagination needed for a feed
    }
}
