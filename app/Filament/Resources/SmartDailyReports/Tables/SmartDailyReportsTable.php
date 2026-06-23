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
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->employee->division->name ?? '-'),
                TextColumn::make('report_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('ai_insight')
                    ->label('AI Insight')
                    ->wrap()
                    ->color('primary')
                    ->limit(100)
                    ->tooltip(fn ($record) => $record->ai_insight),
                TextColumn::make('raw_report')
                    ->label('Laporan Asli')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('division')
                    ->label('Filter Divisi')
                    ->relationship('employee.division', 'name'),
                \Filament\Tables\Filters\Filter::make('report_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('report_date')
                            ->label('Tanggal Laporan'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['report_date'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('report_date', $date),
                            );
                    }),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
