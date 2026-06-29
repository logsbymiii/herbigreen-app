<x-filament-widgets::widget>
    <x-filament::section>
        <div style="max-width: 380px; margin: 0 auto;">
            @php
                $monthName = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->translatedFormat('F Y');
                $days = ['S', 'S', 'R', 'K', 'J', 'S', 'M']; // Senin..Minggu
            @endphp

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: bold;">{{ $monthName }}</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button wire:click="previousMonth" style="color: #9ca3af; background: none; border: none; cursor: pointer;">
                        <x-filament::icon icon="heroicon-m-chevron-left" class="h-5 w-5 text-gray-400" style="height: 20px; width: 20px;" />
                    </button>
                    <button wire:click="nextMonth" style="color: #9ca3af; background: none; border: none; cursor: pointer; {{ $currentMonth == now()->month && $currentYear == now()->year ? 'opacity: 0.3;' : '' }}" @if($currentMonth == now()->month && $currentYear == now()->year) disabled @endif>
                        <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5 text-gray-400" style="height: 20px; width: 20px;" />
                    </button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.5rem; text-align: center; margin-bottom: 0.5rem; font-size: 0.875rem;">
                @foreach($days as $day)
                    <div style="color: #9ca3af; font-weight: 500;">{{ $day }}</div>
                @endforeach
            </div>

            <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.5rem; text-align: center; font-size: 0.875rem;">
                @php
                    $firstDayOffset = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->dayOfWeekIso - 1;
                @endphp
                
                @for($i = 0; $i < $firstDayOffset; $i++)
                    <div></div>
                @endfor

                @foreach($calendarData as $day)
                    @php
                        $dayDateStr = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, $day['date'])->format('Y-m-d');
                    @endphp
                    <a wire:click="$dispatch('filterByDate', { date: '{{ $dayDateStr }}' })" class="{{ $day['isToday'] ? '' : 'hover:bg-gray-100 dark:hover:bg-gray-800' }}" style="text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0.25rem; border-radius: 0.5rem; cursor: pointer; transition: background-color 0.2s; {{ $day['isToday'] ? 'background-color: #10b981; color: white; font-weight: bold;' : 'color: inherit;' }}">
                        <span>{{ $day['date'] }}</span>
                        @if($day['status'] === 'success')
                            <div style="width: 6px; height: 6px; border-radius: 50%; background-color: {{ $day['isToday'] ? '#ffffff' : '#10b981' }}; margin-top: 4px;"></div>
                        @elseif($day['status'] === 'warning')
                            <div style="width: 6px; height: 6px; border-radius: 50%; background-color: #f59e0b; margin-top: 4px; {{ $day['isToday'] ? 'box-shadow: 0 0 0 1px white;' : '' }}"></div>
                        @elseif($day['status'] === 'incomplete')
                            <div style="width: 6px; height: 6px; border-radius: 50%; background-color: #ef4444; margin-top: 4px; {{ $day['isToday'] ? 'box-shadow: 0 0 0 1px white;' : '' }}"></div>
                        @else
                            <div style="width: 6px; height: 6px; border-radius: 50%; margin-top: 4px; opacity: 0;"></div>
                        @endif
                    </a>
                @endforeach
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem; font-size: 0.75rem; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 0.75rem; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center;"><div style="width: 8px; height: 8px; border-radius: 50%; background-color: #10b981; margin-right: 6px;"></div> Hadir Semua</div>
                    <div style="display: flex; align-items: center;"><div style="width: 8px; height: 8px; border-radius: 50%; background-color: #ef4444; margin-right: 6px;"></div> Belum Lengkap</div>
                    <div style="display: flex; align-items: center;"><div style="width: 8px; height: 8px; border-radius: 50%; background-color: #f59e0b; margin-right: 6px;"></div> Ada Izin/Sakit</div>
                </div>
                <!-- <div>
                    {{ $this->exportAction }}
                </div> -->
            </div>
        </div>

        <x-filament-actions::modals />
    </x-filament::section>
</x-filament-widgets::widget>
