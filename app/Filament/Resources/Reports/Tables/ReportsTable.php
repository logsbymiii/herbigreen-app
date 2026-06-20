<?php

namespace App\Filament\Resources\Reports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name') // <-- Ini kuncinya biar keluar nama, bukan angka
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipe Laporan')
                    ->badge() // Bikin jadi badge warna
                    ->color(fn (string $state): string => match ($state) {
                        'harian' => 'success', // Warna hijau
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => str($state)->headline()),

                \Filament\Tables\Columns\ImageColumn::make('media_path')
                    ->label('Lampiran')
                    ->disk('r2')
                    ->visibility('private')
                    ->square()
                    ->defaultImageUrl(fn ($record) => $record->media_path ? \Illuminate\Support\Facades\Storage::disk('r2')->temporaryUrl($record->media_path, now()->addMinutes(10)) : null)
                    ->action(
                        \Filament\Tables\Actions\Action::make('viewMedia')
                            ->label('Lihat Lampiran')
                            ->icon('heroicon-o-eye')
                            ->modalHeading('Lampiran Laporan')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalContent(fn ($record) => $record->media_path ? new \Illuminate\Support\HtmlString('<img src="' . \Illuminate\Support\Facades\Storage::disk('r2')->temporaryUrl($record->media_path, now()->addMinutes(10)) . '" style="width: 100%; border-radius: 8px;" />') : new \Illuminate\Support\HtmlString('<p>Tidak ada lampiran</p>'))
                    ),

                TextColumn::make('content')
                    ->label('Isi Laporan')
                    ->limit(50) // Potong teks biar tabel nggak kepanjangan ke bawah
                    ->searchable(),

                TextColumn::make('reported_at')
                    ->label('Waktu Lapor')
                    ->dateTime('d M Y H:i') // Format tanggal biar rapi
                    ->sortable(),
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
