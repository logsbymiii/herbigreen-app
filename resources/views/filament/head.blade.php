<style>
/* ============================================================
   HERBIGREEN — Nuclear Reset Approach
   Reset semua background, rebuild yang penting
   ============================================================ */

/* STEP 1: Body */
html, body { background-color: #cbd5e1 !important; }

/* STEP 2: Reset SEMUA background di dalam body ke transparent */
body * { background-color: transparent !important; }

/* STEP 3: Override langsung — kasih balik warna yang diperlukan */

/* Sidebar */
.fi-sidebar,
aside.fi-sidebar,
nav.fi-sidebar {
    background-color: #f8f9ff !important;
    border-right: 1px solid #e2e8f0 !important;
}

/* Topbar */
header.fi-topbar,
nav.fi-topbar {
    background-color: rgba(248,249,255,0.95) !important;
    border-bottom: 1px solid #e2e8f0 !important;
    box-shadow: none !important;
}

/* Stat Cards */
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

/* Widget & chart cards */
.fi-wi {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 1px 6px rgba(0,0,0,0.05) !important;
    overflow: hidden !important;
}

/* Section */
.fi-section {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    overflow: hidden !important;
}

/* Input & Form */
input, select, textarea,
.fi-input-wrapper,
.fi-select-input,
[class*="fi-input"] {
    background-color: #f8f9ff !important;
}

/* Dropdown */
.fi-dropdown-panel,
[class*="fi-dropdown-panel"] {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 14px !important;
    box-shadow: 0 8px 30px rgba(0,0,0,0.09) !important;
}

/* Modal */
.fi-modal-window,
[class*="fi-modal"] {
    background-color: #ffffff !important;
    border-radius: 16px !important;
}

/* Table header */
.fi-ta-header-cell {
    background-color: #f8fafc !important;
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    color: #94a3b8 !important;
}
.fi-ta-row:hover .fi-ta-cell { background-color: #f0fdf8 !important; }

/* Button */
.fi-btn { border-radius: 10px !important; font-weight: 600 !important; }

/* Scrollbar */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-thumb { background: #cbd5e1 !important; border-radius: 999px; }

/* Animation */
.fi-main { animation: hbFade 0.35s ease-out; }
@keyframes hbFade {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Sidebar nav hover */
.fi-sidebar-item-button {
    border-radius: 10px !important;
    transition: all 0.18s ease !important;
}
.fi-sidebar-item-button:hover { background-color: rgba(16,185,129,0.08) !important; }
.fi-sidebar-item-active .fi-sidebar-item-button {
    background-color: rgba(16,185,129,0.12) !important;
    border: 1.5px solid rgba(16,185,129,0.3) !important;
}
</style>
