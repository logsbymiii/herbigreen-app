<?php

namespace App\Filament\Resources\Attendances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\Layout\Stack::make([
                    \Filament\Tables\Columns\Layout\Split::make([
                        TextColumn::make('employee.name')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('type')
                            ->badge()
                            ->grow(false)
                            ->color(fn (string $state): string => match ($state) {
                                'hadir' => 'success',
                                'wfh' => 'info',
                                'sakit' => 'warning',
                                'izin' => 'warning',
                                'cuti' => 'primary',
                                'alpa' => 'danger',
                                'telat' => 'danger',
                                default => 'gray',
                            }),
                    ]),
                    
                    \Filament\Tables\Columns\Layout\Split::make([
                        TextColumn::make('date')
                            ->date('d M Y')
                            ->icon('heroicon-o-calendar')
                            ->color('gray')
                            ->sortable(),
                        TextColumn::make('clocked_in_at')
                            ->icon('heroicon-o-clock')
                            ->grow(false)
                            ->color(fn ($state, $record) => $state && \Carbon\Carbon::parse($state)->format('H:i') > '08:30' && in_array($record->type, ['hadir', 'wfh']) ? 'danger' : 'gray')
                            ->formatStateUsing(function ($state, $record) {
                                if (!$state) return '-';
                                $time = \Carbon\Carbon::parse($state)->format('H:i');
                                if (in_array($record->type, ['hadir', 'wfh']) && $time > '08:30') {
                                    return $time . ' (Telat)';
                                }
                                return $time;
                            })
                            ->sortable(),
                    ])->extraAttributes(['class' => 'mt-2']),
                    
                    TextColumn::make('note')
                        ->searchable()
                        ->wrap()
                        ->color('gray')
                        ->size(\Filament\Support\Enums\TextSize::Small)
                        ->icon('heroicon-o-document-text')
                        ->formatStateUsing(fn ($state) => $state ? "Catatan: {$state}" : '')
                        ->extraAttributes(['class' => 'mt-2']),

                    \Filament\Tables\Columns\ImageColumn::make('proof_path')
                        ->disk('r2')
                        ->visibility('private')
                        ->square()
                        ->getStateUsing(function ($record) {
                            if (!$record->proof_path) return null;
                            if (str_starts_with($record->proof_path, 'http')) {
                                return $record->proof_path;
                            }
                            return $record->proof_path;
                        })
                        ->action(
                            \Filament\Actions\Action::make('viewProof')
                                ->label('Lihat Bukti')
                                ->icon('heroicon-o-eye')
                                ->modalHeading('Bukti Kehadiran')
                                ->modalSubmitAction(false)
                                ->modalCancelActionLabel('Tutup')
                                ->modalContent(function ($record) {
                                    if (!$record->proof_path) {
                                        return new \Illuminate\Support\HtmlString('<p>Tidak ada bukti lampiran</p>');
                                    }
                                    $url = str_starts_with($record->proof_path, 'http') 
                                        ? $record->proof_path 
                                        : \Illuminate\Support\Facades\Storage::disk('r2')->temporaryUrl($record->proof_path, now()->addMinutes(10));
                                    return new \Illuminate\Support\HtmlString('<img src="' . $url . '" style="width: 100%; border-radius: 8px;" />');
                                })
                        )
                        ->extraAttributes(['class' => 'mt-2']),
                ])->space(2)
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('employee_id')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Filter Karyawan'),
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'hadir' => 'Hadir',
                        'wfh' => 'WFH',
                        'sakit' => 'Sakit',
                        'izin' => 'Izin',
                        'cuti' => 'Cuti',
                        'alpa' => 'Alpa',
                        'telat' => 'Telat',
                    ])
                    ->label('Filter Status'),
                \Filament\Tables\Filters\Filter::make('bulan_tahun')
                    ->form([
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options([
                                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                                '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                                '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                                '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                            ]),
                        \Filament\Forms\Components\Select::make('year')
                            ->label('Tahun')
                            ->options(array_combine(range(2023, now()->year), range(2023, now()->year))),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when($data['month'], fn ($q, $month) => $q->whereMonth('date', $month))
                            ->when($data['year'], fn ($q, $year) => $q->whereYear('date', $year));
                    }),
                \Filament\Tables\Filters\Filter::make('date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal')
                            ->maxDate(now()),
                        \Filament\Forms\Components\DatePicker::make('date_until')
                            ->label('Sampai Tanggal')
                            ->maxDate(now()),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\AttendanceExporter::class)
                    ->color('primary'),
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
