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
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipe Kehadiran')
                    ->badge(),
                \Filament\Tables\Columns\ImageColumn::make('proof_path')
                    ->label('Bukti Kehadiran')
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
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('clocked_in_at')
                    ->label('Jam')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state, $record) => $state && \Carbon\Carbon::parse($state)->format('H:i') > '08:30' && in_array($record->type, ['hadir', 'wfh']) ? 'danger' : 'success')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return '-';
                        $time = \Carbon\Carbon::parse($state)->format('H:i');
                        if (in_array($record->type, ['hadir', 'wfh']) && $time > '08:30') {
                            return $time . ' (Telat)';
                        }
                        return $time;
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
