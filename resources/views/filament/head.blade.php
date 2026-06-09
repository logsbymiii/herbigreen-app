<style>
/* ============================================================
   HERBIGREEN — Filament v5 Final Theme
   Confirmed working via DevTools admin:116
   ============================================================ */

/* ── 1. BODY BACKGROUND ── */
html, body, body.fi-body, body.fi-body.fi-panel-admin {
    background-color: #cbd5e1 !important; /* slate-300 - visible di mobile */
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}

/* ── 2. SEMUA WRAPPER JADI TRANSPARAN ── */
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

/* ── 3. SIDEBAR ── */
.fi-sidebar, aside.fi-sidebar {
    background-color: #f8f9ff !important;
    border-right: 1px solid #e2e8f0 !important;
}

.fi-sidebar-header { background-color: transparent !important; border-bottom: 1px solid #e2e8f0 !important; }

.fi-sidebar-item-button {
    border-radius: 10px !important;
    transition: all 0.18s ease !important;
}
.fi-sidebar-item-button:hover {
    background-color: rgba(16,185,129,0.08) !important;
    transform: translateX(2px) !important;
}
.fi-sidebar-item-active .fi-sidebar-item-button {
    background-color: rgba(16,185,129,0.12) !important;
    border: 1.5px solid rgba(16,185,129,0.3) !important;
    border-radius: 10px !important;
}

/* ── 4. TOPBAR ── */
header.fi-topbar {
    background-color: rgba(248,249,255,0.95) !important;
    backdrop-filter: blur(10px) !important;
    border-bottom: 1px solid #e2e8f0 !important;
    box-shadow: none !important;
}

/* ── 5. STAT CARDS ── */
.fi-wi-stats-overview-stat {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06) !important;
    transition: transform 0.2s, box-shadow 0.2s !important;
}
.fi-wi-stats-overview-stat:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 24px rgba(16,185,129,0.14) !important;
}

/* ── 6. WIDGET/CHART CARDS ── */
.fi-wi {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 1px 6px rgba(0,0,0,0.05) !important;
    overflow: hidden !important;
}
.fi-wi:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08) !important; }

/* ── 7. SECTION ── */
.fi-section {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    overflow: hidden !important;
}

/* ── 8. TABLE ── */
.fi-ta-header-cell {
    background-color: #f8fafc !important;
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    color: #94a3b8 !important;
}
.fi-ta-row:hover .fi-ta-cell { background-color: #f0fdf8 !important; }

/* ── 9. BUTTONS ── */
.fi-btn { border-radius: 10px !important; font-weight: 600 !important; }

/* ── 10. DROPDOWN ── */
.fi-dropdown-panel {
    border-radius: 14px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 8px 30px rgba(0,0,0,0.09) !important;
    overflow: hidden !important;
}

/* ── 11. SCROLLBAR ── */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* ── 12. ANIMATION ── */
.fi-main { animation: hbFade 0.35s ease-out; }
@keyframes hbFade {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── 13. DESKTOP — lebih gelap biar keliatan beda ── */
@media (min-width: 1024px) {
    body.fi-body { background-color: #94a3b8 !important; }
    .fi-layout { background-color: #f0f4f8 !important; border-radius: 0 !important; }
    .fi-sidebar { background-color: #f8f9ff !important; }
}

/* ── 14. LOGIN PAGE ── */
body.fi-simple-layout {
    background: linear-gradient(135deg, #ecfdf5, #f8f9ff, #ecfdf5) !important;
}
body.fi-simple-layout .fi-simple-page {
    border-radius: 20px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 20px 60px rgba(0,108,73,0.1) !important;
}
</style>
