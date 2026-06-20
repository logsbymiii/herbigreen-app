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
                    ->defaultImageUrl(fn ($record) => $record->proof_path ? \Illuminate\Support\Facades\Storage::disk('r2')->temporaryUrl($record->proof_path, now()->addMinutes(10)) : null)
                    ->action(
                        \Filament\Tables\Actions\Action::make('viewProof')
                            ->label('Lihat Bukti')
                            ->icon('heroicon-o-eye')
                            ->modalHeading('Bukti Kehadiran')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalContent(fn ($record) => $record->proof_path ? new \Illuminate\Support\HtmlString('<img src="' . \Illuminate\Support\Facades\Storage::disk('r2')->temporaryUrl($record->proof_path, now()->addMinutes(10)) . '" style="width: 100%; border-radius: 8px;" />') : new \Illuminate\Support\HtmlString('<p>Tidak ada bukti lampiran</p>'))
                    ),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
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
