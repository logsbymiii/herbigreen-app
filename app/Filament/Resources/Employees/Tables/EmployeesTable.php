<?php

namespace App\Filament\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Nomor HP')
                    ->searchable()
                    ->formatStateUsing(function (string $state) {
                        if (preg_match('/^62(\d{3})(\d{3,4})(\d{3,4})$/', $state, $matches)) {
                            return '+62 ' . $matches[1] . '-' . $matches[2] . '-' . $matches[3];
                        }
                        return '+' . substr($state, 0, 2) . ' ' . substr($state, 2);
                    }),
                ToggleColumn::make('is_active')
                    ->label('Status Aktif'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Group::make('division.name')
                    ->label('Divisi')
                    ->collapsible(),
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
