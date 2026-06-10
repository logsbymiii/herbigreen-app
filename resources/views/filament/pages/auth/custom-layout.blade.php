<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Herbigreen HR Platform</title>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "surface-container-lowest": "#ffffff",
                        "on-surface-variant": "#3c4a42",
                        "on-error-container": "#93000a",
                        "on-tertiary": "#ffffff",
                        "on-primary-fixed-variant": "#005236",
                        "secondary": "#565e74",
                        "outline": "#6c7a71",
                        "surface-container-high": "#dce9ff",
                        "inverse-primary": "#4edea3",
                        "background": "#f8f9ff",
                        "on-surface": "#0b1c30",
                        "tertiary-container": "#9699ff",
                        "tertiary": "#494bd6",
                        "surface-variant": "#d3e4fe",
                        "on-background": "#0b1c30",
                        "outline-variant": "#bbcabf",
                        "on-tertiary-container": "#1d17b2",
                        "surface-tint": "#006c49",
                        "secondary-fixed-dim": "#bec6e0",
                        "error": "#ba1a1a",
                        "surface-container-low": "#eff4ff",
                        "on-secondary-fixed": "#131b2e",
                        "surface": "#f8f9ff",
                        "secondary-container": "#dae2fd",
                        "on-secondary-fixed-variant": "#3f465c",
                        "surface-bright": "#f8f9ff",
                        "primary-fixed-dim": "#4edea3",
                        "surface-container": "#e5eeff",
                        "on-secondary-container": "#5c647a",
                        "inverse-surface": "#213145",
                        "primary": "#006c49",
                        "surface-dim": "#cbdbf5",
                        "on-primary-container": "#00422b",
                        "tertiary-fixed": "#e1e0ff",
                        "primary-container": "#10b981",
                        "inverse-on-surface": "#eaf1ff",
                        "on-secondary": "#ffffff",
                        "on-error": "#ffffff",
                        "on-primary": "#ffffff",
                        "surface-container-highest": "#d3e4fe",
                        "primary-fixed": "#6ffbbe",
                        "tertiary-fixed-dim": "#c0c1ff",
                        "on-tertiary-fixed-variant": "#2f2ebe",
                        "on-tertiary-fixed": "#07006c",
                        "secondary-fixed": "#dae2fd",
                        "on-primary-fixed": "#002113",
                        "error-container": "#ffdad6"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "fontFamily": {
                        "headline-md": ["Plus Jakarta Sans"],
                        "headline-lg-mobile": ["Plus Jakarta Sans"],
                        "body-sm": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"],
                        "headline-xl": ["Plus Jakarta Sans"]
                    },
                    "fontSize": {
                        "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                        "body-sm": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "label-md": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                    }
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8f9ff;
            min-height: max(884px, 100dvh);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
    @livewireStyles
    @filamentStyles
</head>
<body class="flex min-h-screen items-center justify-center p-4">
    <!-- Atmospheric Background Element -->
    <div class="fixed inset-0 z-[-1] overflow-hidden">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px]"></div>
        <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-surface-container-high/40 rounded-full blur-[120px]"></div>
    </div>
    
    <main class="w-full max-w-[480px] animate-in fade-in slide-in-from-bottom-4 duration-700">
        <!-- Logo Section -->
        <div class="flex flex-col items-center mb-10">
            <div class="flex items-center justify-center mb-2">
                <img src="{{ asset('images/logo-herbigreen.png') }}" alt="Herbigreen Logo" class="h-20 w-auto object-contain">
            </div>
        </div>
        
        <!-- Login Card -->
        <div class="glass-card rounded-xl p-8 md:p-10 shadow-xl relative">
            {{ $slot }}
        </div>
        
        <!-- Visual Footer Decorative Element -->
        <div class="mt-12 flex justify-center gap-8 opacity-40 grayscale hover:grayscale-0 transition-all duration-500">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">verified_user</span>
                <span class="font-label-md text-[10px] uppercase tracking-widest">Enterprise Secure</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">cloud_done</span>
                <span class="font-label-md text-[10px] uppercase tracking-widest">Real-time Sync</span>
            </div>
        </div>
    </main>

    @livewire('notifications')
    @livewireScripts
    @filamentScripts
    
    <script>
        // Simple micro-interactions
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('mousedown', () => {
                button.style.transform = 'scale(0.96)';
            });
            button.addEventListener('mouseup', () => {
                button.style.transform = '';
            });
        });
    </script>
</body>
</html>
