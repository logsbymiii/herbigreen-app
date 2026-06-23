<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $monthName = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->translatedFormat('F Y');
            $days = ['S', 'S', 'R', 'K', 'J', 'S', 'M']; // Senin..Minggu
        @endphp

        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">{{ $monthName }}</h2>
            <div class="flex space-x-2">
                <button wire:click="previousMonth" class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                    <x-heroicon-o-chevron-left class="w-5 h-5"/>
                </button>
                <button wire:click="nextMonth" class="text-gray-400 hover:text-gray-900 dark:hover:text-white transition" @if($currentMonth == now()->month && $currentYear == now()->year) disabled style="opacity: 0.3;" @endif>
                    <x-heroicon-o-chevron-right class="w-5 h-5"/>
                </button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.5rem; text-align: center; margin-bottom: 0.5rem;" class="text-xs sm:text-sm">
            @foreach($days as $day)
                <div class="text-gray-400 font-medium">{{ $day }}</div>
            @endforeach
        </div>

        <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.5rem; text-align: center;" class="text-sm">
            @php
                $firstDayOffset = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->dayOfWeekIso - 1;
            @endphp
            
            @for($i = 0; $i < $firstDayOffset; $i++)
                <div></div>
            @endfor

            @foreach($calendarData as $day)
                <div class="flex flex-col items-center justify-center p-1 sm:p-2 rounded-lg {{ $day['isToday'] ? 'bg-primary-600 text-white font-bold' : '' }}">
                    <span>{{ $day['date'] }}</span>
                    @if($day['status'] === 'success')
                        <div class="w-1.5 h-1.5 rounded-full mt-1" style="background-color: #10b981;"></div>
                    @elseif($day['status'] === 'warning')
                        <div class="w-1.5 h-1.5 rounded-full mt-1" style="background-color: #f59e0b;"></div>
                    @else
                        <div class="w-1.5 h-1.5 rounded-full mt-1 opacity-0"></div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex space-x-4 mt-6 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-3">
            <div class="flex items-center"><div class="w-2 h-2 rounded-full mr-2" style="background-color: #10b981;"></div> Lengkap</div>
            <div class="flex items-center"><div class="w-2 h-2 rounded-full mr-2" style="background-color: #f59e0b;"></div> Ada izin/sakit</div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
