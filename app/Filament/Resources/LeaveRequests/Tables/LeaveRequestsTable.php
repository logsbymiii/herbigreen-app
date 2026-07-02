<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveRequestsTable
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
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sakit' => 'danger',
                        'izin' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('note')
                    ->label('Alasan')
                    ->limit(50),
                \Filament\Tables\Columns\ImageColumn::make('proof_path')
                    ->label('Bukti')
                    ->disk('r2')
                    ->visibility('private')
                    ->square()
                    ->action(
                        \Filament\Actions\Action::make('viewBukti')
                            ->label('Lihat Bukti')
                            ->icon('heroicon-o-eye')
                            ->modalHeading('Bukti')
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
                TextColumn::make('request_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('responded_at')
                    ->label('Direspon')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
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
