<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\Employee;
use Carbon\Carbon;

class ChartDetailsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $status;
    public string $chartType;

    public function mount(string $status, string $chartType)
    {
        $this->status = $status;
        $this->chartType = $chartType;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $today = Carbon::today();
                $query = Employee::query()->with('division');
                $statusKey = strtolower($this->status);

                if ($this->chartType === 'attendance') {
                    if (in_array($statusKey, ['hadir', 'wfh', 'sakit', 'izin'])) {
                        return $query->whereHas('attendances', function($q) use ($today, $statusKey) {
                            $q->whereDate('date', $today)->where('type', $statusKey);
                        });
                    } else {
                        return $query->whereDoesntHave('attendances', function($q) use ($today) {
                            $q->whereDate('date', $today);
                        });
                    }
                } elseif ($this->chartType === 'report') {
                    if ($statusKey === 'sudah lapor') {
                        return $query->whereHas('smartDailyReports', function($q) use ($today) {
                            $q->whereDate('report_date', $today);
                        });
                    } else {
                        return $query->whereDoesntHave('smartDailyReports', function($q) use ($today) {
                            $q->whereDate('report_date', $today);
                        });
                    }
                }

                return $query;
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->searchable()
                    ->sortable()
                    ->default('-'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    public function render()
    {
        return view('livewire.chart-details-table');
    }
}
