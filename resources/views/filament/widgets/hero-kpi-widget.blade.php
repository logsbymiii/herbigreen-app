<x-filament-widgets::widget>
    <x-filament::section class="!bg-gradient-to-br !from-[#10B981] !to-[#059669] shadow-lg !border-0" style="border-radius: 20px; overflow: hidden;">
        <div class="p-4 sm:p-6 text-white flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div class="space-y-2">
                <p class="text-sm sm:text-base font-medium opacity-80 uppercase tracking-wider">Laporan Masuk Hari Ini</p>
                <div class="flex items-end gap-3">
                    <h2 class="text-4xl sm:text-5xl font-bold tracking-tight">{{ $laporanMasuk }}</h2>
                    <span class="text-lg opacity-90 font-medium mb-1">/ {{ $totalKaryawan }} Karyawan</span>
                </div>
                
                <div class="flex items-center gap-2 mt-4 inline-flex px-3 py-1 bg-white/20 rounded-full text-sm font-medium">
                    @if($trend > 0)
                        <x-heroicon-m-arrow-trending-up class="w-4 h-4 text-white" />
                        <span>Naik {{ $trend }}% dari kemarin</span>
                    @elseif($trend < 0)
                        <x-heroicon-m-arrow-trending-down class="w-4 h-4 text-white" />
                        <span>Turun {{ abs($trend) }}% dari kemarin</span>
                    @else
                        <x-heroicon-m-minus class="w-4 h-4 text-white" />
                        <span>Sama dengan kemarin</span>
                    @endif
                </div>
            </div>
            
            <div class="mt-6 sm:mt-0 opacity-20">
                <!-- Large Decorative Icon -->
                <x-heroicon-o-document-text class="w-32 h-32 absolute -right-4 -top-8 transform rotate-12" />
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
