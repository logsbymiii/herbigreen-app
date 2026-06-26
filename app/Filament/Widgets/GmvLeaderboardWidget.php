<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\ImageColumn;

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
                    ->orderByRaw('(SELECT COALESCE(SUM(gmv_amount), 0) FROM gmv_reports WHERE gmv_reports.employee_id = employees.id AND MONTH(created_at) = ? AND YEAR(created_at) = ? AND gmv_reports.deleted_at IS NULL) DESC', [now()->month, now()->year])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Host Live')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-o-user')
                    ->description('Tim Host Live Herbigreen'),
                Tables\Columns\TextColumn::make('gmv_today_sum_gmv_amount')
                    ->label('Omset Hari Ini')
                    ->default(0)
                    ->money('IDR', locale: 'id')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('(SELECT COALESCE(SUM(gmv_amount), 0) FROM gmv_reports WHERE gmv_reports.employee_id = employees.id AND DATE(created_at) = ? AND gmv_reports.deleted_at IS NULL) ' . $direction, [now()->format('Y-m-d')]);
                    })
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-arrow-trending-up'),
                Tables\Columns\TextColumn::make('gmv_this_month_sum_gmv_amount')
                    ->label('Total Omset Bulan Ini (Rank)')
                    ->default(0)
                    ->money('IDR', locale: 'id')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('(SELECT COALESCE(SUM(gmv_amount), 0) FROM gmv_reports WHERE gmv_reports.employee_id = employees.id AND MONTH(created_at) = ? AND YEAR(created_at) = ? AND gmv_reports.deleted_at IS NULL) ' . $direction, [now()->month, now()->year]);
                    })
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-s-trophy')
                    ->size('lg'),
            ])
            ->heading('🏆 GMV Leaderboard (Host Live)')
            ->description('Peringkat performa omset Host Live bulan ini.')
            ->defaultPaginationPageOption(5)
            ->paginated(false)
            ->striped();
    }
}
