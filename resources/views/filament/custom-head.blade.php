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
