<?php

namespace App\Filament\Resources\GmvReports\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;

class GmvReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Host Live')
                    ->searchable()
                    ->sortable(),

                ImageColumn::make('screenshot_path')
                    ->label('Bukti Live')
                    ->circular()
                    ->defaultImageUrl(url('images/default-screenshot.png')),

               TextColumn::make('gmv_amount')
                    ->label('Total GMV')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable(),

                TextColumn::make('live_date')
                    ->label('Tanggal Live')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Diinput Pada')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([ // <-- Ubah jadi actions() bawaan standar Filament
                EditAction::make(),
            ])
            ->bulkActions([ // <-- Ubah jadi bulkActions() bawaan standar Filament
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
