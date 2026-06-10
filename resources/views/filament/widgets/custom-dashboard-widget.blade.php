<x-filament-widgets::widget class="!bg-transparent !border-none !shadow-none !overflow-visible">
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <div class="custom-dashboard-wrapper text-on-surface">
        <div class="flex flex-col gap-4">
            <!-- Summary Stats (Mobile Grid) -->
            <div class="grid grid-cols-2 gap-3">
                <!-- Stat Card 1 -->
                <div class="glass-card p-4 flex flex-col active:scale-[0.98] transition-transform">
                    <div class="flex justify-between items-start mb-3">
                        <div class="p-1.5 rounded-lg bg-primary/10 text-primary">
                            <span class="material-symbols-outlined text-[20px]">group</span>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-0.5">{{ number_format($totalKaryawan) }}</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Total Karyawan</p>
                    </div>
                </div>
                <!-- Stat Card 2 -->
                <div class="glass-card p-4 flex flex-col active:scale-[0.98] transition-transform">
                    <div class="flex justify-between items-start mb-3">
                        <div class="p-1.5 rounded-lg bg-tertiary-container/20 text-tertiary">
                            <span class="material-symbols-outlined text-[20px]">assignment</span>
                        </div>
                        <span class="font-label-md text-label-md text-tertiary bg-tertiary-container/20 px-2 py-0.5 rounded-full">Hari Ini</span>
                    </div>
                    <div>
                        <h3 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-0.5">{{ number_format($laporanMasuk) }}</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Laporan Masuk</p>
                    </div>
                </div>
                <!-- Stat Card 3 -->
                <div class="glass-card p-4 flex flex-col active:scale-[0.98] transition-transform">
                    <div class="flex justify-between items-start mb-3">
                        <div class="p-1.5 rounded-lg bg-error/10 text-error">
                            <span class="material-symbols-outlined text-[20px]">sick</span>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-0.5">{{ number_format($izinHariIni) }}</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Izin/Sakit</p>
                    </div>
                </div>
                <!-- Stat Card 4 -->
                <div class="glass-card p-4 flex flex-col active:scale-[0.98] transition-transform">
                    <div class="flex justify-between items-start mb-3">
                        <div class="p-1.5 rounded-lg bg-secondary/10 text-secondary">
                            <span class="material-symbols-outlined text-[20px]">pending_actions</span>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-0.5">{{ number_format($belumLapor) }}</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Belum Lapor</p>
                    </div>
                </div>
            </div>

            <!-- Analytics Charts Section -->
            <div class="flex flex-col gap-4">
                <!-- Main Trend Chart -->
                <div class="glass-card p-4 flex flex-col min-h-[300px]">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-headline-md text-body-lg text-on-surface font-semibold">Tren Mingguan</h3>
                            <p class="font-body-sm text-label-md text-on-surface-variant font-normal">Aktivitas 7 hari terakhir</p>
                        </div>
                        <button class="p-1 -mr-1 text-on-surface-variant active:bg-surface-container rounded-full transition-colors">
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                    </div>
                    <!-- Chart Mockup -->
                    <div class="flex-1 relative w-full h-[200px] flex items-end pt-2">
                        <!-- Y-Axis Labels -->
                        <div class="absolute left-0 top-0 h-[170px] flex flex-col justify-between text-[10px] text-on-surface-variant py-2">
                            <span>1000</span>
                            <span>500</span>
                            <span>0</span>
                        </div>
                        <!-- Grid Lines -->
                        <div class="absolute inset-0 ml-8 border-l border-b border-surface-container-high flex flex-col justify-between py-2 h-[170px]">
                            <div class="w-full border-t border-surface-container-high border-dashed"></div>
                            <div class="w-full border-t border-surface-container-high border-dashed"></div>
                            <div class="w-full"></div>
                        </div>
                        <!-- SVG Line Chart Mockup -->
                        <div class="absolute inset-0 ml-8 mb-[30px] pt-2 overflow-hidden h-[170px]">
                            <svg class="w-full h-full" preserveaspectratio="none" viewbox="0 0 1000 300">
                                <defs>
                                    <lineargradient id="emeraldGradient" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#10b981" stop-opacity="0.2"></stop>
                                        <stop offset="100%" stop-color="#10b981" stop-opacity="0"></stop>
                                    </lineargradient>
                                </defs>
                                <!-- Fill -->
                                <path d="M0,250 L150,180 L300,210 L450,120 L600,160 L750,80 L900,130 L1000,50 L1000,300 L0,300 Z" fill="url(#emeraldGradient)"></path>
                                <!-- Line -->
                                <path d="M0,250 L150,180 L300,210 L450,120 L600,160 L750,80 L900,130 L1000,50" fill="none" stroke="#10b981" stroke-linecap="round" stroke-linejoin="round" stroke-width="4"></path>
                            </svg>
                        </div>
                        <!-- X-Axis Labels -->
                        <div class="absolute bottom-0 left-0 w-full ml-8 flex justify-between text-[10px] text-on-surface-variant pr-2">
                            <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                        </div>
                    </div>
                </div>

                <!-- Secondary Charts Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Bar Chart: Laporan per Divisi -->
                    <div class="glass-card p-4 flex flex-col h-[220px]">
                        <h3 class="font-body-md text-body-md text-on-surface font-semibold mb-3">Laporan per Divisi</h3>
                        <div class="flex-1 flex justify-between gap-2 pt-2 border-b border-surface-container-high pb-2">
                            <div class="w-1/4 h-full flex flex-col items-center justify-end gap-1.5">
                                <div class="w-full max-w-[32px] bg-primary/20 rounded-t-md h-[80%] relative"></div>
                                <span class="text-[10px] text-on-surface-variant">Sales</span>
                            </div>
                            <div class="w-1/4 h-full flex flex-col items-center justify-end gap-1.5">
                                <div class="w-full max-w-[32px] bg-primary/60 rounded-t-md h-[95%] relative"></div>
                                <span class="text-[10px] text-on-surface-variant">Ops</span>
                            </div>
                            <div class="w-1/4 h-full flex flex-col items-center justify-end gap-1.5">
                                <div class="w-full max-w-[32px] bg-primary/40 rounded-t-md h-[60%] relative"></div>
                                <span class="text-[10px] text-on-surface-variant">HR</span>
                            </div>
                            <div class="w-1/4 h-full flex flex-col items-center justify-end gap-1.5">
                                <div class="w-full max-w-[32px] bg-primary rounded-t-md h-[85%] relative"></div>
                                <span class="text-[10px] text-on-surface-variant">Tech</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rasio Kehadiran Hari Ini -->
                    <div class="glass-card p-4 flex flex-col relative">
                        <h3 class="font-body-md text-body-md text-on-surface font-semibold mb-6">Rasio Kehadiran Hari Ini</h3>
                        <div class="flex-1 flex flex-col items-center justify-center">
                            <!-- SVG Donut Chart -->
                            <div class="relative w-40 h-40 mb-6">
                                <svg class="w-full h-full transform -rotate-90" viewbox="0 0 100 100">
                                    @php
                                        $offset2 = - $laporanDash;
                                        $offset3 = - ($laporanDash + $belumLaporDash);
                                    @endphp
                                    <circle cx="50" cy="50" fill="transparent" r="30" stroke="#10b981" stroke-dasharray="{{ $laporanDash }} {{ $circumference }}" stroke-dashoffset="0" stroke-width="20"></circle>
                                    <circle cx="50" cy="50" fill="transparent" r="30" stroke="#ef4444" stroke-dasharray="{{ $belumLaporDash }} {{ $circumference }}" stroke-dashoffset="{{ $offset2 }}" stroke-width="20"></circle>
                                    <circle cx="50" cy="50" fill="transparent" r="30" stroke="#eab308" stroke-dasharray="{{ $izinDash }} {{ $circumference }}" stroke-dashoffset="{{ $offset3 }}" stroke-width="20"></circle>
                                </svg>
                            </div>
                            <!-- Legend -->
                            <div class="flex gap-4 justify-center text-[11px] text-on-surface-variant font-medium">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2.5 h-2.5 bg-[#10b981]"></div>
                                    <span>Hadir/Lapor</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2.5 h-2.5 bg-[#eab308]"></div>
                                    <span>Izin/Sakit</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2.5 h-2.5 bg-[#ef4444]"></div>
                                    <span>Belum Lapor</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Table (Mobile Card List) -->
            <div class="glass-card overflow-hidden">
                <div class="p-4 border-b border-surface-container-high flex justify-between items-center bg-surface-container-lowest">
                    <h3 class="font-body-md text-body-md text-on-surface font-semibold">Aktivitas Terkini</h3>
                    <button class="font-label-md text-[12px] text-primary font-medium p-1 -mr-1 hover:underline">Lihat Semua</button>
                </div>
                <div class="flex flex-col divide-y divide-surface-container-high">
                    <!-- Mobile List Items -->
                    @forelse($recentReports as $report)
                    <div class="p-4 flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <div class="flex items-center gap-3">
                            @php
                                $initials = strtoupper(substr($report->employee->name ?? '?', 0, 2));
                                $colors = ['bg-primary/20 text-primary', 'bg-tertiary-container/20 text-tertiary', 'bg-secondary/20 text-secondary', 'bg-error/20 text-error'];
                                $color = $colors[crc32($report->employee->name ?? 'a') % 4];
                            @endphp
                            <div class="w-10 h-10 rounded-full {{ $color }} flex items-center justify-center font-bold text-sm shrink-0">{{ $initials }}</div>
                            <div class="flex flex-col">
                                <span class="font-body-sm text-body-sm text-on-surface font-medium">{{ $report->employee->name ?? 'Unknown' }}</span>
                                <span class="text-[11px] text-on-surface-variant">{{ $report->employee->division->name ?? '-' }} • {{ $report->created_at->format('h:i A') }}</span>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-semibold bg-primary/10 text-primary">Selesai</span>
                    </div>
                    @empty
                    <div class="p-4 flex items-center justify-center text-sm text-on-surface-variant">Belum ada aktivitas laporan.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
