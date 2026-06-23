<x-filament-widgets::widget>
    <div style="background: linear-gradient(135deg, #10B981, #059669); border-radius: 20px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);">
        <div class="p-6 text-white flex flex-col sm:flex-row justify-between items-start sm:items-center relative">
            <div class="space-y-2 relative z-10">
                <p class="text-sm sm:text-base font-medium uppercase tracking-wider" style="opacity: 0.8;">Laporan Masuk Hari Ini</p>
                <div class="flex items-end gap-3">
                    <h2 class="font-bold tracking-tight" style="font-size: 3rem; line-height: 1;">{{ $laporanMasuk }}</h2>
                    <span class="text-lg font-medium mb-1" style="opacity: 0.9;">/ {{ $totalKaryawan }} Karyawan</span>
                </div>
                
                <div class="flex items-center gap-2 mt-4 inline-flex px-3 py-1 rounded-full text-sm font-medium" style="background: rgba(255,255,255,0.2);">
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
            
            <div class="mt-6 sm:mt-0 absolute right-4 top-4" style="opacity: 0.15; transform: rotate(12deg);">
                <x-heroicon-o-document-text style="width: 120px; height: 120px;" />
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
