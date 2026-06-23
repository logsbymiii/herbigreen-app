<?php

namespace App\Filament\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('card')
                    ->label('')
                    ->view('filament.tables.columns.employee-card')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    }),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->groups([
                Group::make('division.name')
                    ->label('Divisi')
                    ->titlePrefixedWithLabel(false)
                    ->collapsible(),
            ])
            ->filters([
                //
            ])
            ->recordUrl(fn ($record) => \App\Filament\Resources\Employees\EmployeeResource::getUrl('edit', ['record' => $record]))
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
