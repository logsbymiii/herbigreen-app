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
                \Filament\Tables\Columns\TextColumn::make('No')
                    ->rowIndex(),
                \Filament\Tables\Columns\TextColumn::make('employee.name')
                    ->label('Nama')
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('type')
                    ->label('Tipe Kehadiran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'wfh' => 'info',
                        'sakit' => 'warning',
                        'izin' => 'warning',
                        'alpa' => 'danger',
                        'telat' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                \Filament\Tables\Columns\TextColumn::make('note')
                    ->label('Keterangan')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                \Filament\Tables\Columns\ImageColumn::make('proof_path')
                    ->label('Bukti Surat')
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
                    ),
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make(),
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
                        'alpa' => 'Alpa',
                        'telat' => 'Telat',
                    ])
                    ->label('Filter Status'),
                \Filament\Tables\Filters\Filter::make('bulan_tahun')
                    ->form([
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options(function () {
                                $months = \App\Models\Attendance::selectRaw('MONTH(date) as month')
                                    ->whereNotNull('date')
                                    ->distinct()
                                    ->orderBy('month')
                                    ->pluck('month')
                                    ->toArray();
                                
                                $monthNames = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];

                                $options = [];
                                foreach ($months as $m) {
                                    $options[str_pad($m, 2, '0', STR_PAD_LEFT)] = $monthNames[(int)$m];
                                }
                                return $options;
                            }),
                        \Filament\Forms\Components\Select::make('year')
                            ->label('Tahun')
                            ->options(function () {
                                $years = \App\Models\Attendance::selectRaw('YEAR(date) as year')
                                    ->whereNotNull('date')
                                    ->distinct()
                                    ->orderByDesc('year')
                                    ->pluck('year')
                                    ->toArray();
                                
                                $options = [];
                                foreach ($years as $y) {
                                    $options[$y] = $y;
                                }
                                return $options;
                            }),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when($data['month'], fn ($q, $month) => $q->whereMonth('date', $month))
                            ->when($data['year'], fn ($q, $year) => $q->whereYear('date', $year));
                    })
                    ->columns(2),
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
                    ->columns(2),
            ])
            ->recordUrl(fn ($record) => \App\Filament\Resources\Attendances\AttendanceResource::getUrl('edit', ['record' => $record]))
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
