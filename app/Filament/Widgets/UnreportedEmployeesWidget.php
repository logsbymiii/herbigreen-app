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
                    ->whereDoesntHave('smartDailyReports', function (Builder $query) use ($today) {
                        $query->whereDate('report_date', $today);
                    })
                    ->whereDoesntHave('attendances', function (Builder $query) use ($today) {
                        $query->whereDate('date', $today)
                              ->whereIn('type', ['sakit', 'izin']);
                    })
            )
            ->columns([
                \Filament\Tables\Columns\Layout\Stack::make([
                    \Filament\Tables\Columns\Layout\Split::make([
                        \Filament\Tables\Columns\ImageColumn::make('employee_avatar')
                            ->getStateUsing(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? 'Deleted') . '&background=f3f4f6&color=374151&bold=true')
                            ->circular()
                            ->grow(false)
                            ->extraImgAttributes(['style' => 'width: 40px; height: 40px;']),
                        
                        \Filament\Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('name')
                                ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                ->searchable()
                                ->sortable(),
                            Tables\Columns\TextColumn::make('division.name')
                                ->badge()
                                ->color('warning')
                                ->sortable(),
                        ])->space(1),
                        
                        Tables\Columns\TextColumn::make('status')
                            ->default('Belum Lapor')
                            ->badge()
                            ->grow(false)
                            ->alignEnd()
                            ->color('danger')
                            ->icon('heroicon-m-x-circle'),
                    ])->extraAttributes(['class' => 'items-center']),
                ])->space(3),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Semua Karyawan Sudah Lapor 🎉')
            ->emptyStateDescription('Tidak ada karyawan yang mangkir laporan hari ini.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated([5, 10, 25]);
    }
}
