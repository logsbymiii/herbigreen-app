<?php

namespace App\Filament\Widgets;

use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HostLiveLeaderboardWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = '🏅 Leaderboard Host Live (Bulan Ini)';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Employee::query()
                    ->whereHas('division', function ($query) {
                        $query->where('name', 'like', '%Host Live%');
                    })
                    ->withSum(['gmvReports' => function ($query) {
                        $query->whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year);
                    }], 'gmv_amount')
                    ->orderByDesc('gmv_reports_sum_gmv_amount')
            )
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('rank')
                    ->label('Peringkat')
                    ->rowIndex(),
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Nama Host')
                    ->searchable()
                    ->weight('bold'),
                \Filament\Tables\Columns\TextColumn::make('gmv_reports_sum_gmv_amount')
                    ->label('Total Omset (Bulan Ini)')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->badge()
                    ->color('success'),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
