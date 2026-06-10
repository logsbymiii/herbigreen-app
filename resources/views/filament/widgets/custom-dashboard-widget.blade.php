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
                    "caption": ["Plus Jakarta Sans"]
                },
                "fontSize": {
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
        }

        .custom-dashboard-wrapper .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        
        .custom-dashboard-wrapper .material-symbols-outlined.fill {
            font-variation-settings: 'FILL' 1;
        }

        .custom-dashboard-wrapper .glass-card {
            background-color: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        .custom-dashboard-wrapper .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .custom-dashboard-wrapper .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <div class="custom-dashboard-wrapper text-on-surface">
        <div class="w-full">
            <!-- Summary Stats (Bento Grid Style) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-gutter mb-gutter">
                <!-- Stat Card 1 -->
                <div class="glass-card rounded-xl p-card-padding flex flex-col justify-between group hover:border-primary/50 transition-all duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 rounded-lg bg-primary/10 text-primary">
                            <span class="material-symbols-outlined">group</span>
                        </div>
                        <span class="font-label-md text-label-md text-primary bg-primary/10 px-2 py-1 rounded-full">+12%</span>
                    </div>
                    <div>
                        <h3 class="font-display-lg text-display-lg font-bold text-on-surface mb-1">{{ number_format($totalKaryawan) }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Total Karyawan</p>
                    </div>
                </div>
                <!-- Stat Card 2 -->
                <div class="glass-card rounded-xl p-card-padding flex flex-col justify-between group hover:border-tertiary-container/50 transition-all duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 rounded-lg bg-tertiary-container/10 text-tertiary">
                            <span class="material-symbols-outlined">assignment</span>
                        </div>
                        <span class="font-label-md text-label-md text-tertiary bg-tertiary-container/10 px-2 py-1 rounded-full">Hari Ini</span>
                    </div>
                    <div>
                        <h3 class="font-display-lg text-display-lg font-bold text-on-surface mb-1">{{ number_format($laporanMasuk) }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Laporan Masuk</p>
                    </div>
                </div>
                <!-- Stat Card 3 -->
                <div class="glass-card rounded-xl p-card-padding flex flex-col justify-between group hover:border-error/50 transition-all duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 rounded-lg bg-error/10 text-error">
                            <span class="material-symbols-outlined">sick</span>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-display-lg text-display-lg font-bold text-on-surface mb-1">{{ number_format($izinHariIni) }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Izin/Sakit</p>
                    </div>
                </div>
                <!-- Stat Card 4 -->
                <div class="glass-card rounded-xl p-card-padding flex flex-col justify-between group hover:border-secondary/50 transition-all duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 rounded-lg bg-secondary/10 text-secondary">
                            <span class="material-symbols-outlined">pending_actions</span>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-display-lg text-display-lg font-bold text-on-surface mb-1">{{ number_format($belumLapor) }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Belum Lapor</p>
                    </div>
                </div>
            </div>
            
            <!-- Analytics Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter mb-gutter">
                <!-- Main Trend Chart (Spans 8 cols) -->
                <div class="glass-card rounded-xl p-card-padding lg:col-span-8 flex flex-col min-h-[400px]">
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-black/5">
                        <div>
                            <h3 class="font-headline-md text-headline-md text-on-surface">Tren Laporan Mingguan</h3>
                            <p class="font-body-md text-body-md text-on-surface-variant">Aktivitas pelaporan 7 hari terakhir</p>
                        </div>
                        <button class="p-2 text-on-surface-variant hover:text-primary transition-colors">
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                    </div>
                    <!-- Chart Mockup -->
                    <div class="flex-1 relative w-full h-full flex items-end pt-4">
                        <!-- Y-Axis Labels -->
                        <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-caption text-on-surface-variant py-8">
                            <span>1000</span>
                            <span>750</span>
                            <span>500</span>
                            <span>250</span>
                            <span>0</span>
                        </div>
                        <!-- Grid Lines -->
                        <div class="absolute inset-0 ml-10 border-l border-b border-black/5 flex flex-col justify-between py-8">
                            <div class="w-full border-t border-black/5 border-dashed"></div>
                            <div class="w-full border-t border-black/5 border-dashed"></div>
                            <div class="w-full border-t border-black/5 border-dashed"></div>
                            <div class="w-full border-t border-black/5 border-dashed"></div>
                            <div class="w-full"></div>
                        </div>
                        <!-- SVG Line Chart Mockup -->
                        <div class="absolute inset-0 ml-10 mb-8 pt-8 overflow-hidden">
                            <svg class="w-full h-full" preserveaspectratio="none" viewbox="0 0 1000 300">
                                <defs>
                                    <lineargradient id="emeraldGradient" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#10b981" stop-opacity="0.2"></stop>
                                        <stop offset="100%" stop-color="#10b981" stop-opacity="0"></stop>
                                    </lineargradient>
                                </defs>
                                <path d="M0,250 L150,180 L300,210 L450,120 L600,160 L750,80 L900,130 L1000,50 L1000,300 L0,300 Z" fill="url(#emeraldGradient)"></path>
                                <path d="M0,250 L150,180 L300,210 L450,120 L600,160 L750,80 L900,130 L1000,50" fill="none" stroke="#10b981" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"></path>
                                <circle class="hover:r-6 transition-all cursor-pointer" cx="150" cy="180" fill="#10b981" r="4"></circle>
                                <circle class="hover:r-6 transition-all cursor-pointer" cx="300" cy="210" fill="#10b981" r="4"></circle>
                                <circle class="hover:r-6 transition-all cursor-pointer" cx="450" cy="120" fill="#10b981" r="4"></circle>
                                <circle class="hover:r-6 transition-all cursor-pointer" cx="600" cy="160" fill="#10b981" r="4"></circle>
                                <circle class="hover:r-6 transition-all cursor-pointer" cx="750" cy="80" fill="#10b981" r="4"></circle>
                                <circle class="hover:r-6 transition-all cursor-pointer" cx="900" cy="130" fill="#10b981" r="4"></circle>
                                <circle class="hover:r-6 transition-all cursor-pointer" cx="1000" cy="50" fill="#10b981" r="4"></circle>
                            </svg>
                        </div>
                        <!-- X-Axis Labels -->
                        <div class="absolute bottom-0 left-0 w-full ml-10 flex justify-between text-caption text-on-surface-variant pr-4">
                            <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                        </div>
                    </div>
                </div>

                <!-- Secondary Charts (Spans 4 cols) -->
                <div class="lg:col-span-4 flex flex-col gap-gutter">
                    <!-- Bar Chart: Laporan per Divisi -->
                    <div class="glass-card rounded-xl p-card-padding flex-1 flex flex-col">
                        <h3 class="font-body-lg text-body-lg text-on-surface font-semibold mb-4">Laporan per Divisi</h3>
                        <div class="flex-1 flex items-end justify-between gap-2 pt-4 border-b border-black/5 pb-2">
                            <div class="w-1/5 flex flex-col items-center gap-2 group">
                                <div class="w-full bg-primary/20 rounded-t-lg h-[80%] group-hover:bg-primary/40 transition-colors relative"></div>
                                <span class="font-caption text-caption text-on-surface-variant">Sales</span>
                            </div>
                            <div class="w-1/5 flex flex-col items-center gap-2 group">
                                <div class="w-full bg-primary/60 rounded-t-lg h-[95%] group-hover:bg-primary/80 transition-colors relative"></div>
                                <span class="font-caption text-caption text-on-surface-variant">Ops</span>
                            </div>
                            <div class="w-1/5 flex flex-col items-center gap-2 group">
                                <div class="w-full bg-primary/40 rounded-t-lg h-[60%] group-hover:bg-primary/60 transition-colors relative"></div>
                                <span class="font-caption text-caption text-on-surface-variant">HR</span>
                            </div>
                            <div class="w-1/5 flex flex-col items-center gap-2 group">
                                <div class="w-full bg-primary/80 rounded-t-lg h-[85%] group-hover:bg-primary transition-colors relative"></div>
                                <span class="font-caption text-caption text-on-surface-variant">Tech</span>
                            </div>
                        </div>
                    </div>
                    <!-- Bubble Chart Text Info -->
                    <div class="glass-card rounded-xl p-card-padding flex-1 flex flex-col relative overflow-hidden">
                        <h3 class="font-body-lg text-body-lg text-on-surface font-semibold mb-2 relative z-10">GMV per Host Live</h3>
                        <p class="font-caption text-caption text-on-surface-variant mb-4 relative z-10">Top performers this week</p>
                        <div class="flex-1 relative flex items-center justify-center min-h-[120px]">
                            <div class="absolute w-20 h-20 rounded-full bg-primary/20 backdrop-blur-md flex items-center justify-center border border-primary/20 -ml-12 mt-4 hover:bg-primary/30 transition-colors cursor-pointer"><span class="font-label-md text-label-md text-primary">A</span></div>
                            <div class="absolute w-24 h-24 rounded-full bg-tertiary-container/30 backdrop-blur-md flex items-center justify-center border border-tertiary-container/20 z-10 hover:bg-tertiary-container/50 transition-colors cursor-pointer"><span class="font-headline-md text-headline-md text-tertiary">B</span></div>
                            <div class="absolute w-16 h-16 rounded-full bg-secondary/20 backdrop-blur-md flex items-center justify-center border border-secondary/20 ml-16 -mt-8 hover:bg-secondary/30 transition-colors cursor-pointer"><span class="font-caption text-caption text-secondary">C</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Table -->
            <div class="glass-card rounded-xl overflow-hidden mb-8">
                <div class="p-card-padding border-b border-black/5 flex justify-between items-center">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Aktivitas Laporan Terkini</h3>
                    <button class="font-label-md text-label-md text-primary hover:underline">Lihat Semua</button>
                </div>
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container border-b border-black/5">
                                <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant font-medium">Nama Karyawan</th>
                                <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant font-medium">Divisi</th>
                                <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant font-medium">Waktu Lapor</th>
                                <th class="py-3 px-6 font-label-md text-label-md text-on-surface-variant font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="font-body-md text-body-md divide-y divide-black/5">
                            @forelse($recentReports as $report)
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="py-4 px-6 flex items-center gap-3">
                                    @php
                                        $initials = strtoupper(substr($report->employee->name ?? '?', 0, 2));
                                        $colors = ['bg-primary/20 text-primary', 'bg-tertiary-container/20 text-tertiary', 'bg-secondary/20 text-secondary', 'bg-error/20 text-error'];
                                        $color = $colors[crc32($report->employee->name ?? 'a') % 4];
                                    @endphp
                                    <div class="w-8 h-8 rounded-full {{ $color }} flex items-center justify-center font-bold text-sm">{{ $initials }}</div>
                                    <span class="text-on-surface">{{ $report->employee->name ?? 'Unknown' }}</span>
                                </td>
                                <td class="py-4 px-6 text-on-surface-variant">{{ $report->employee->division->name ?? '-' }}</td>
                                <td class="py-4 px-6 text-on-surface-variant">{{ $report->created_at->format('h:i A') }}</td>
                                <td class="py-4 px-6"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary border border-primary/20">Masuk</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-4 px-6 text-center text-on-surface-variant">Belum ada aktivitas laporan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
