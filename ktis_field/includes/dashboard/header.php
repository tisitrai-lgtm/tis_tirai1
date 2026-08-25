<?php
/**
 * includes/dashboard/header.php — Dashboard Specific Styles & Scripts
 */
?>
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: ['class', '.dark-mode'],
        theme: {
            extend: {
                colors: {
                    ktis: {
                        50: '#f0fdf4', 100: '#dcfce7', 500: '#10b981', 
                        600: '#059669', 700: '#047857', 800: '#065f46', 900: '#064e3b',
                    },
                    brand: { primary: '#e11d48', dark: '#1e293b' }
                },
                fontFamily: { sarabun: ['Sarabun', 'sans-serif'] }
            }
        }
    }
</script>
<link rel="stylesheet" href="global_smoothness.css">
<style>
    body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; }
    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }
    .modal-backdrop {
        background-color: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
    }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .pulse-subtle { animation: pulse 2s cubic-bezier(0.4, 0, 0.2, 1) infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .7; } }

    /* ══════════════════════════════════════════
       DARK MODE OVERRIDES
       ══════════════════════════════════════════ */
    .dark-mode body,
    html.dark-mode body {
        background-color: #090d16 !important;
        color: #f1f5f9 !important;
    }
    .dark-mode .main-content,
    .dark-mode .page-wrapper {
        background-color: #090d16 !important;
    }
    .dark-mode .glass-card {
        background: #131b2e !important;
        border-color: #1e293b !important;
    }
    .dark-mode h1,
    .dark-mode h2,
    .dark-mode h3 {
        color: #f8fafc !important;
    }
    .dark-mode table thead tr {
        background: #0b1120 !important;
        border-color: #1e293b !important;
        color: #94a3b8 !important;
    }
    .dark-mode table tbody td {
        color: #cbd5e1 !important;
        border-color: #1e293b !important;
    }
    .dark-mode table tbody tr:hover {
        background: #1e293b !important;
    }
    /* iOS Safari & WebKit Date Input & Form Control Fix */
    input[type="date"],
    input[type="time"],
    input[type="datetime-local"],
    input[type="text"],
    select {
        -webkit-appearance: none;
        appearance: none;
        box-sizing: border-box !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }
    input[type="date"]::-webkit-date-and-time-value {
        text-align: left !important;
        min-height: 1.3em;
    }
</style>