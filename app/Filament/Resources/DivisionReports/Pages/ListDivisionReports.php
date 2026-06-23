<?php

namespace App\Filament\Resources\DivisionReports\Pages;

use App\Filament\Resources\DivisionReports\DivisionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Division;

class ListDivisionReports extends ListRecords
{
    protected static string $resource = DivisionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('Semua')];

        $divisions = Division::all();
        foreach ($divisions as $division) {
            $tabs[$division->id] = Tab::make($division->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('employee.division', function($q) use ($division) {
                    $q->where('id', $division->id);
                }));
        }

        return $tabs;
    }
}
