<?php

namespace App\Filament\Resources\SmartDailyReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SmartDailyReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\Layout\Stack::make([
                    \Filament\Tables\Columns\Layout\Split::make([
                        \Filament\Tables\Columns\ImageColumn::make('employee_avatar')
                            ->getStateUsing(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->employee?->name ?? 'Deleted') . '&background=f3f4f6&color=374151&bold=true')
                            ->circular()
                            ->grow(false)
                            ->extraImgAttributes(['style' => 'width: 40px; height: 40px;']),
                        
                        \Filament\Tables\Columns\Layout\Stack::make([
                            TextColumn::make('employee.name')
                                ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                ->searchable()
                                ->sortable(),
                            TextColumn::make('date_and_division')
                                ->getStateUsing(function ($record) {
                                    $date = \Carbon\Carbon::parse($record->report_date)->translatedFormat('d M Y');
                                    $div = $record->employee->division->name ?? 'Tidak Ada Divisi';
                                    return "{$div} · {$date}";
                                })
                                ->color('gray')
                                ->size(\Filament\Support\Enums\TextSize::Small),
                        ])->space(1),
                    ])->extraAttributes(['class' => 'items-center']),

                    \Filament\Tables\Columns\Layout\Stack::make([
                        TextColumn::make('ai_insight')
                            ->wrap()
                            ->formatStateUsing(fn ($state) => '<div>' . \Illuminate\Support\Str::markdown($state ?? '') . '</div>')
                            ->html()
                            ->color('primary')
                            ->extraAttributes(['class' => 'mt-2 text-sm']),
                    ]),
                ])->space(3),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('division')
                    ->label('Filter Divisi')
                    ->relationship('employee.division', 'name'),
                \Filament\Tables\Filters\Filter::make('report_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('report_date')
                            ->label('Tanggal Laporan')
                            ->default(now()->toDateString()),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['report_date'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('report_date', $date),
                            );
                    }),
                \Filament\Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
                \Filament\Actions\RestoreAction::make(),
                \Filament\Actions\ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
