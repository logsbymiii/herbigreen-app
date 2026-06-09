<style>
/* ============================================================
   HERBIGREEN — Inline Style Override untuk Filament v5
   Diinjek langsung ke HTML biar nggak ada caching issue
   ============================================================ */

/* Override di luar semua @layer — ini yang paling prioritas */
@layer herbigreen {
    html { background-color: #dde3ea; }
    body { background-color: #dde3ea; }
}

/* Selectors tanpa layer — paling kuat */
html                              { background-color: #dde3ea !important; }
body                              { background-color: #dde3ea !important; }
.fi-body                          { background-color: #dde3ea !important; }
.fi-panel-admin                   { background-color: #dde3ea !important; }

/* Sidebar */
.fi-sidebar                       { background-color: #f8f9ff !important; }
aside[class*="fi-sidebar"]        { background-color: #f8f9ff !important; }

/* Topbar */
header[class*="fi-topbar"]        { background-color: rgba(248,249,255,0.95) !important; box-shadow: none !important; border-bottom: 1px solid #e2e8f0 !important; }

/* Stat cards */
[class*="fi-wi-stats-overview-stat"] { background-color: #ffffff !important; border: 1px solid #e2e8f0 !important; border-radius: 16px !important; }

/* Widget cards */
[class*="fi-wi"]:not([class*="stat"]) { background-color: #ffffff !important; border: 1px solid #e2e8f0 !important; border-radius: 16px !important; }

/* Main layout container — desktop card effect */
@media (min-width: 1024px) {
    [class*="fi-layout"]          { background: #f8f9ff !important; border-radius: 20px !important; margin: 12px !important; box-shadow: 0 4px 40px rgba(0,0,0,0.08) !important; }
}

/* Scrollbar */
::-webkit-scrollbar              { width: 5px; height: 5px; }
::-webkit-scrollbar-thumb        { background: #cbd5e1; border-radius: 999px; }
</style>
