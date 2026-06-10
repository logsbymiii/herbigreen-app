<x-filament-widgets::widget class="!bg-transparent !border-none !shadow-none !overflow-visible">
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
    tailwind.config = {
        darkMode: "class",
        corePlugins: { preflight: false },
        important: ".custom-dashboard-wrapper",
        theme: {
            extend: {
                "colors": {
                    "surface-dim": "#cbdbf5",
                    "on-background": "#0b1c30",
                    "surface-container-highest": "#d3e4fe",
                    "surface": "#f8f9ff",
                    "background": "#f8f9ff",
                    "on-tertiary-fixed-variant": "#2f2ebe",
                    "surface-container-high": "#dce9ff",
                    "surface-container": "#e5eeff",
                    "tertiary-container": "#9699ff",
                    "tertiary": "#494bd6",
                    "on-tertiary-container": "#1d17b2",
                    "on-tertiary-fixed": "#07006c",
                    "on-surface-variant": "#3c4a42",
                    "surface-container-low": "#eff4ff",
                    "secondary-fixed-dim": "#bec6e0",
                    "on-tertiary": "#ffffff",
                    "on-secondary-fixed": "#131b2e",
                    "on-primary": "#ffffff",
                    "error-container": "#ffdad6",
                    "outline-variant": "#bbcabf",
                    "inverse-surface": "#213145",
                    "inverse-on-surface": "#eaf1ff",
                    "tertiary-fixed-dim": "#c0c1ff",
                    "on-error-container": "#93000a",
                    "tertiary-fixed": "#e1e0ff",
                    "on-secondary": "#ffffff",
                    "on-secondary-container": "#5c647a",
                    "on-secondary-fixed-variant": "#3f465c",
                    "error": "#ba1a1a",
                    "primary-fixed-dim": "#4edea3",
                    "on-surface": "#0b1c30",
                    "primary-container": "#10b981",
                    "on-primary-fixed": "#002113",
                    "secondary-container": "#dae2fd",
                    "secondary": "#565e74",
                    "on-primary-container": "#00422b",
                    "on-error": "#ffffff",
                    "surface-variant": "#d3e4fe",
                    "outline": "#6c7a71",
                    "secondary-fixed": "#dae2fd",
                    "primary-fixed": "#6ffbbe",
                    "on-primary-fixed-variant": "#005236",
                    "surface-container-lowest": "#ffffff",
                    "primary": "#006c49",
                    "surface-bright": "#f8f9ff",
                    "inverse-primary": "#4edea3",
                    "surface-tint": "#006c49"
                },
                "spacing": {
                    "card-padding": "24px",
                    "gutter": "24px",
                },
                "fontFamily": {
                    "headline-md": ["Plus Jakarta Sans"],
                    "body-md": ["Plus Jakarta Sans"],
                    "headline-lg": ["Plus Jakarta Sans"],
                    "display-lg": ["Plus Jakarta Sans"],
                    "body-lg": ["Plus Jakarta Sans"],
                    "headline-lg-mobile": ["Plus Jakarta Sans"],
                    "label-md": ["Plus Jakarta Sans"],
                    "caption": ["Plus Jakarta Sans"],
                    "body-sm": ["Plus Jakarta Sans"]
                },
                "fontSize": {
                    "body-sm": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                    "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                    "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                    "headline-lg": ["32px", { "lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "700" }],
                    "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "800" }],
                    "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                    "headline-lg-mobile": ["24px", { "lineHeight": "32px", "fontWeight": "700" }],
                    "label-md": ["14px", { "lineHeight": "20px", "letterSpacing": "0.05em", "fontWeight": "500" }],
                    "caption": ["12px", { "lineHeight": "16px", "fontWeight": "400" }]
                }
            }
        }
    }
    </script>

    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        .custom-dashboard-wrapper {
            background-color: transparent;
            color: var(--color-on-surface);
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .custom-dashboard-wrapper .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        
        .custom-dashboard-wrapper .material-symbols-outlined.fill {
            font-variation-settings: 'FILL' 1;
        }

        .custom-dashboard-wrapper .glass-card {
            background-color: theme('colors.surface-container-lowest');
            border: 1px solid theme('colors.surface-container-high');
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .custom-dashboard-wrapper .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .custom-dashboard-wrapper .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .custom-dashboard-wrapper .emerald-gradient-fill {
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0) 100%);
        }
    </style>

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
                        <div class="flex-1 flex items-end justify-between gap-2 pt-2 border-b border-surface-container-high pb-2">
                            <div class="w-1/4 flex flex-col items-center gap-1.5">
                                <div class="w-full max-w-[32px] bg-primary/20 rounded-t-md h-[80%] relative"></div>
                                <span class="text-[10px] text-on-surface-variant">Sales</span>
                            </div>
                            <div class="w-1/4 flex flex-col items-center gap-1.5">
                                <div class="w-full max-w-[32px] bg-primary/60 rounded-t-md h-[95%] relative"></div>
                                <span class="text-[10px] text-on-surface-variant">Ops</span>
                            </div>
                            <div class="w-1/4 flex flex-col items-center gap-1.5">
                                <div class="w-full max-w-[32px] bg-primary/40 rounded-t-md h-[60%] relative"></div>
                                <span class="text-[10px] text-on-surface-variant">HR</span>
                            </div>
                            <div class="w-1/4 flex flex-col items-center gap-1.5">
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
