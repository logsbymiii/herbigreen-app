<style>
/* ============================================================
   HERBIGREEN — Filament v5 Theme (v4 - targeted fix)
   Strategy: transparin semua wrapper, putihkan cards aja
   ============================================================ */

/* 1. BODY background */
html, body, body.fi-body { background-color: #dde3ea !important; }

/* 2. Semua fi-* wrapper jadi transparent kecuali cards */
.fi-layout,
.fi-main,
.fi-page,
.fi-sc-component,
.fi-sc-has-gap,
.fi-grid,
div[class*="fi-main"],
div[class*="fi-page"],
div[class*="fi-layout"] {
    background-color: transparent !important;
}

/* 3. SIDEBAR — tetap putih */
.fi-sidebar,
aside.fi-sidebar {
    background-color: #f8f9ff !important;
    border-right: 1px solid #e2e8f0 !important;
}

.fi-sidebar-header { background-color: transparent !important; }

.fi-sidebar-item-button:hover {
    background-color: rgba(16,185,129,0.08) !important;
    transform: translateX(2px) !important;
}

.fi-sidebar-item-active .fi-sidebar-item-button {
    background-color: rgba(16,185,129,0.12) !important;
    border: 1.5px solid rgba(16,185,129,0.3) !important;
    border-radius: 10px !important;
}

/* 4. TOPBAR — putih semi transparan */
header.fi-topbar {
    background-color: rgba(248,249,255,0.95) !important;
    backdrop-filter: blur(10px) !important;
    border-bottom: 1px solid #e2e8f0 !important;
    box-shadow: none !important;
}

/* 5. STAT CARDS — putih dengan shadow */
.fi-wi-stats-overview-stat {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
    transition: transform 0.2s, box-shadow 0.2s !important;
}

.fi-wi-stats-overview-stat:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 24px rgba(16,185,129,0.14) !important;
}

/* 6. CHART & TABLE WIDGET — putih */
.fi-wi {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 1px 6px rgba(0,0,0,0.04) !important;
    overflow: hidden !important;
}

/* 7. SECTION — putih */
.fi-section {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    overflow: hidden !important;
}

/* 8. TABLE */
.fi-ta-header-cell {
    background-color: #f8fafc !important;
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    color: #94a3b8 !important;
}

/* 9. BUTTON */
.fi-btn { border-radius: 10px !important; font-weight: 600 !important; }

/* 10. SCROLLBAR */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }

/* 11. MOBILE */
@media (max-width: 1023px) {
    html, body, body.fi-body { background-color: #f1f5f9 !important; }
    .fi-wi-stats-overview-stat, .fi-wi, .fi-section { border-radius: 14px !important; }
}

/* 12. DESKTOP: layout shadow */
@media (min-width: 1024px) {
    body.fi-body { background-color: #c8d0da !important; }
}
</style>
