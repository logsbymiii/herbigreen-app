<style>
/* ============================================================
   HERBIGREEN — CSS berdasarkan class names ASLI dari DevTools
   ============================================================ */

/* Background seluruh halaman */
html,
body,
body.fi-body,
body.fi-body.fi-panel-admin {
    background-color: #dde3ea !important;
}

/* Wrapper Livewire & layout utama */
body > div,
body.fi-body > div {
    background-color: #dde3ea !important;
    min-height: 100vh;
}

/* Grid/section container - class asli dari DevTools */
.fi-sc,
.fi-sc-component {
    background-color: transparent !important;
}

/* Desktop: layout card effect */
@media (min-width: 1024px) {
    .fi-layout,
    div[class*="fi-layout"] {
        background: #f8f9ff !important;
        border-radius: 20px !important;
        margin: 12px !important;
        box-shadow: 0 4px 40px rgba(0,0,0,0.09) !important;
        overflow: hidden !important;
    }
}

/* Sidebar */
.fi-sidebar,
nav.fi-sidebar,
aside.fi-sidebar {
    background-color: #f8f9ff !important;
}

/* Topbar */
header.fi-topbar {
    background-color: rgba(248,249,255,0.95) !important;
    box-shadow: none !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

/* Stats cards */
.fi-wi-stats-overview-stat {
    background-color: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
}

.fi-wi-stats-overview-stat:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 24px rgba(16,185,129,0.12) !important;
    transition: all 0.2s ease !important;
}

/* Widget cards */
.fi-wi {
    background-color: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 1px 6px rgba(0,0,0,0.04) !important;
}

/* Section */
.fi-section {
    background-color: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #e2e8f0 !important;
}

/* Page header */
.fi-page-header,
.fi-page-header-main-ctn {
    padding-bottom: 1rem !important;
}

/* Table */
.fi-ta-header-cell {
    background-color: #f8fafc !important;
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    color: #94a3b8 !important;
}

/* Buttons */
.fi-btn {
    border-radius: 10px !important;
    font-weight: 600 !important;
}

/* Scrollbar */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }

/* Fade in animasi */
.fi-main { animation: hbFade 0.3s ease-out; }
@keyframes hbFade {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Mobile */
@media (max-width: 1023px) {
    html, body, body.fi-body { background-color: #f1f5f9 !important; }
    body > div, body.fi-body > div { background-color: #f1f5f9 !important; }
}
</style>
