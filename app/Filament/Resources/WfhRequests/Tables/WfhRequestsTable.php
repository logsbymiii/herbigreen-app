<?php

namespace App\Filament\Resources\WfhRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WfhRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\Layout\Stack::make([
                    \Filament\Tables\Columns\Layout\Split::make([
                        \Filament\Tables\Columns\ImageColumn::make('employee_avatar')
                            ->getStateUsing(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->employee?->name ?? 'Deleted') . '&background=f3f4f6&color=374151&bold=true')
                            ->circular()
                            ->grow(false)
                            ->extraImgAttributes(['style' => 'width: 40px; height: 40px;']),
                        
                        \Filament\Tables\Columns\Layout\Stack::make([
                            TextColumn::make('employee.name')
                                ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                ->searchable()
                                ->sortable(),
                            TextColumn::make('date_and_reason')
                                ->getStateUsing(function ($record) {
                                    $date = \Carbon\Carbon::parse($record->request_date)->translatedFormat('d M Y');
                                    return "Pengajuan WFH · {$date}";
                                })
                                ->color('gray')
                                ->size(\Filament\Support\Enums\TextSize::Small),
                        ])->space(1),
                        
                        TextColumn::make('status')
                            ->badge()
                            ->grow(false)
                            ->alignEnd()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => ucfirst($state)),
                    ])->extraAttributes(['class' => 'items-center']),

                    \Filament\Tables\Columns\Layout\Stack::make([
                        TextColumn::make('reason')
                            ->label('Alasan')
                            ->wrap()
                            ->searchable()
                            ->extraAttributes(['class' => 'mt-2 text-sm text-gray-600 dark:text-gray-400']),
                    ]),
                ])->space(3),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
                \Filament\Actions\RestoreAction::make(),
                \Filament\Actions\ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
