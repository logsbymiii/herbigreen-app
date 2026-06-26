<?php

namespace App\Filament\Resources\GmvReports\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteBulkAction;

class GmvReportsTable
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
                            TextColumn::make('date_info')
                                ->getStateUsing(function ($record) {
                                    $date = \Carbon\Carbon::parse($record->live_date)->translatedFormat('d M Y');
                                    return "Tanggal Live · {$date}";
                                })
                                ->color('gray')
                                ->size(\Filament\Support\Enums\TextSize::Small),
                        ])->space(1),
                        
                        TextColumn::make('gmv_amount')
                            ->money('IDR', locale: 'id_ID')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->color('success')
                            ->grow(false)
                            ->alignEnd(),
                    ])->extraAttributes(['class' => 'items-center']),

                    \Filament\Tables\Columns\Layout\Stack::make([
                        ImageColumn::make('screenshot_path')
                            ->height(150)
                            ->width('100%')
                            ->extraImgAttributes(['style' => 'object-fit: cover; border-radius: 0.5rem; margin-top: 0.5rem;']),
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
