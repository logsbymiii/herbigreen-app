<style>
/* ============================================================
   HERBIGREEN — Custom Filament v5 Theme (FIXED)
   CSS CONFIRMED WORKING via DevTools admin:128
   ============================================================ */

/* ── Background luar ── */
html,
body,
body.fi-body,
body.fi-body.fi-panel-admin {
    background-color: #dde3ea !important;
}

/* ── Wrapper Livewire — kasih background sesuai ── */
body > div,
body.fi-body > div {
    background-color: inherit !important;
}

/* ── Sidebar ── */
.fi-sidebar,
nav.fi-sidebar,
aside.fi-sidebar {
    background-color: #f8f9ff !important;
    border-right: 1px solid #e2e8f0 !important;
}

.fi-sidebar-header {
    background-color: transparent !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

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
}

/* ── Topbar ── */
header.fi-topbar {
    background-color: rgba(248,249,255,0.95) !important;
    backdrop-filter: blur(10px) !important;
    box-shadow: none !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

/* ── Main content area ── */
.fi-main {
    background-color: transparent !important;
}

/* ── Desktop: layout window effect ── */
@media (min-width: 1024px) {
    .fi-layout {
        background-color: #f0f4f8 !important;
        border-radius: 20px !important;
        margin: 12px !important;
        box-shadow: 0 4px 40px rgba(0,0,0,0.08) !important;
        overflow: hidden !important;
    }
}

/* ── Stat Cards ── */
.fi-wi-stats-overview-stat {
    background-color: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease !important;
}

.fi-wi-stats-overview-stat:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 24px rgba(16,185,129,0.12) !important;
}

/* ── Widget & Chart Cards ── */
.fi-wi {
    background-color: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 1px 6px rgba(0,0,0,0.04) !important;
    overflow: hidden !important;
    transition: box-shadow 0.2s ease !important;
}

.fi-wi:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.07) !important;
}

/* ── Section ── */
.fi-section {
    background-color: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #e2e8f0 !important;
    overflow: hidden !important;
}

/* ── Table ── */
.fi-ta-header-cell {
    background-color: #f8fafc !important;
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    color: #94a3b8 !important;
}

.fi-ta-row:hover .fi-ta-cell {
    background-color: #f0fdf8 !important;
}

/* ── Buttons ── */
.fi-btn {
    border-radius: 10px !important;
    font-weight: 600 !important;
}

/* ── Dropdown ── */
.fi-dropdown-panel {
    border-radius: 14px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 8px 30px rgba(0,0,0,0.08) !important;
}

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* ── Fade animation ── */
.fi-main { animation: hbFade 0.3s ease-out; }
@keyframes hbFade {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── Mobile ── */
@media (max-width: 1023px) {
    html, body, body.fi-body { background-color: #f1f5f9 !important; }
}
</style>
