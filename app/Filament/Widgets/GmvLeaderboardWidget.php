<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class GmvLeaderboardWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->whereHas('division', function (Builder $query) {
                        $query->where('name', 'like', '%Host Live%');
                    })
                    ->withSum(['gmvReports as gmv_today' => function ($query) {
                        $query->whereDate('created_at', now()->format('Y-m-d'));
                    }], 'gmv_amount')
                    ->withSum(['gmvReports as gmv_this_month' => function ($query) {
                        $query->whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year);
                    }], 'gmv_amount')
                    ->orderByDesc('gmv_this_month_sum_gmv_amount')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Host Live')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('gmv_today_sum_gmv_amount')
                    ->label('GMV Hari Ini')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('gmv_this_month_sum_gmv_amount')
                    ->label('Total GMV (Bulan Ini)')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
            ])
            ->heading('🏆 GMV Leaderboard (Host Live)')
            ->defaultPaginationPageOption(5)
            ->paginated(false); // To show it as a clean leaderboard
    }
}
