<?php
/**
 * Global Theme System
 * Include this file in the <head> section of all pages to enable global theme support
 * Usage: <?php include_once 'includes/global_theme.php'; ?>
 */
?>
<!-- Global Theme System -->
<script>
    (function() {
        // Detect saved theme from localStorage before page renders
        const savedTheme = localStorage.getItem('theme') || 'light';
        const htmlElement = document.documentElement;
        htmlElement.setAttribute('data-theme', savedTheme);
        
        // Pre-apply dark theme class to avoid flash of light theme
        if (savedTheme === 'dark') {
            document.write('<style>body { background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%); color: #e0e0e0; transition: background 0.3s ease, color 0.3s ease; }</style>');
            document.addEventListener('DOMContentLoaded', function() {
                document.body.classList.add('dark-theme');
            });
        }
    })();
</script>

<style>
    /* Root CSS Variables */
    :root {
        --primary: #1a4e8a;
        --primary-dark: #0d3a6c;
        --primary-light: #2c6cb0;
        --secondary: #e9b949;
        --secondary-dark: #d4a337;
        --light: #f8f9fa;
        --dark: #212529;
        --gray: #6c757d;
        --gray-light: #e9ecef;
        --success: #28a745;
        --success-light: #d4edda;
        --warning: #ffc107;
        --warning-light: #fff3cd;
        --danger: #dc3545;
        --danger-light: #f8d7da;
        --info: #17a2b8;
        --info-light: #d1ecf1;
        --white: #ffffff;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --border-radius: 12px;
        --border-radius-lg: 16px;
    }

    /* Light Theme (Default) */
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        color: #212529;
        transition: background 0.3s ease, color 0.3s ease;
    }

    body.light-theme {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        color: #212529;
    }

    /* Dark Theme */
    body.dark-theme {
        background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%);
        color: #e0e0e0;
    }

    body.dark-theme .navbar {
        background: linear-gradient(135deg, #0f3460 0%, #162a47 100%);
        border-color: #2a3a50;
    }

    body.dark-theme .content-card,
    body.dark-theme .form-section,
    body.dark-theme .stat-card,
    body.dark-theme .card {
        background: #2a2a3e;
        color: #e0e0e0;
        border-color: #404052;
    }

    body.dark-theme .dashboard-header {
        background: linear-gradient(135deg, #0f3460 0%, #162a47 100%);
        color: #e0e0e0;
    }

    body.dark-theme .dashboard-footer {
        background: #2a2a3e;
        color: #e0e0e0;
        border-color: #404052;
    }

    body.dark-theme .form-control,
    body.dark-theme .form-select,
    body.dark-theme .form-check-input {
        background: #3a3a4e;
        color: #e0e0e0;
        border-color: #505062;
    }

    body.dark-theme .form-control:focus,
    body.dark-theme .form-select:focus {
        background: #424455;
        color: #e0e0e0;
        border-color: #1a4e8a;
        box-shadow: 0 0 0 0.2rem rgba(26, 78, 138, 0.25);
    }

    body.dark-theme .card-header {
        background: #3a3a4e;
        color: #e0e0e0;
        border-color: #404052;
    }

    body.dark-theme h1,
    body.dark-theme h2,
    body.dark-theme h3,
    body.dark-theme h4,
    body.dark-theme h5,
    body.dark-theme h6 {
        color: #e0e0e0;
    }

    body.dark-theme .text-muted {
        color: #a0a0b0;
    }

    body.dark-theme .text-secondary {
        color: #9ca3af;
    }

    body.dark-theme .setting-label {
        color: #e0e0e0;
    }

    body.dark-theme .activity-item {
        background: #3a3a4e;
        border-color: #404052;
    }

    body.dark-theme table {
        color: #e0e0e0;
        border-color: #404052;
    }

    body.dark-theme .table th {
        background: #3a3a4e;
        color: #e0e0e0;
        border-color: #404052;
    }

    body.dark-theme .table td {
        color: #e0e0e0;
        border-color: #404052;
    }

    body.dark-theme .table-striped tbody tr:nth-of-type(odd) {
        background: #32323f;
    }

    body.dark-theme .alert {
        background: #3a3a4e;
        color: #e0e0e0;
        border-color: #404052;
    }

    body.dark-theme .alert-info {
        background: #1a2d47;
        border-color: #2a4d7f;
        color: #a8d5f7;
    }

    body.dark-theme .alert-success {
        background: #1a3a2e;
        border-color: #2a6a4f;
        color: #a8e6c7;
    }

    body.dark-theme .alert-warning {
        background: #3a3120;
        border-color: #6a5a3f;
        color: #f7d9a8;
    }

    body.dark-theme .alert-danger {
        background: #3a1a20;
        border-color: #6a2a3f;
        color: #f7a8c8;
    }

    body.dark-theme .profile-completion {
        background: linear-gradient(135deg, #0f3460 0%, #162a47 100%);
        color: #e0e0e0;
    }

    body.dark-theme .modal-content {
        background: #2a2a3e;
        color: #e0e0e0;
        border-color: #404052;
    }

    body.dark-theme .modal-header {
        background: #3a3a4e;
        border-color: #404052;
        color: #e0e0e0;
    }

    body.dark-theme .modal-footer {
        background: #3a3a4e;
        border-color: #404052;
    }

    body.dark-theme .btn-secondary {
        background: #505062;
        border-color: #505062;
        color: #e0e0e0;
    }

    body.dark-theme .btn-secondary:hover {
        background: #606072;
        border-color: #606072;
    }

    body.dark-theme .dropdown-menu {
        background: #2a2a3e;
        color: #e0e0e0;
        border-color: #404052;
    }

    body.dark-theme .dropdown-item {
        color: #e0e0e0;
    }

    body.dark-theme .dropdown-item:hover,
    body.dark-theme .dropdown-item:focus {
        background: #3a3a4e;
        color: #e0e0e0;
    }

    body.dark-theme .dropdown-divider {
        border-color: #404052;
    }

    body.dark-theme .nav-tabs {
        border-color: #404052;
    }

    body.dark-theme .nav-tabs .nav-link {
        color: #a0a0b0;
        border-color: #404052;
    }

    body.dark-theme .nav-tabs .nav-link.active {
        background: #3a3a4e;
        color: #e0e0e0;
        border-color: #1a4e8a;
    }

    body.dark-theme .badge {
        background: #3a3a4e;
        color: #e0e0e0;
    }

    body.dark-theme .badge-primary {
        background: #1a4e8a;
        color: #e0e0e0;
    }

    body.dark-theme .badge-success {
        background: #2a5f3f;
        color: #a8e6c7;
    }

    body.dark-theme .badge-danger {
        background: #5a2a3f;
        color: #f7a8c8;
    }

    body.dark-theme .badge-warning {
        background: #6a5a2a;
        color: #f7d9a8;
    }

    body.dark-theme hr {
        border-color: #404052;
        opacity: 0.5;
    }

    body.dark-theme .btn-outline-primary {
        color: #8cc5ff;
        border-color: #1a4e8a;
    }

    body.dark-theme .btn-outline-primary:hover {
        background: #1a4e8a;
        border-color: #1a4e8a;
        color: #e0e0e0;
    }

    /* Scrollbar */
    body.dark-theme ::-webkit-scrollbar {
        width: 8px;
    }

    body.dark-theme ::-webkit-scrollbar-track {
        background: #3a3a4e;
    }

    body.dark-theme ::-webkit-scrollbar-thumb {
        background: #505062;
        border-radius: 4px;
    }

    body.dark-theme ::-webkit-scrollbar-thumb:hover {
        background: #606072;
    }
</style>

<script>
    /**
     * Global Theme Management Function
     * Call this function to change the theme across all pages
     */
    function applyTheme(theme) {
        localStorage.setItem('theme', theme);
        document.documentElement.setAttribute('data-theme', theme);
        
        // Remove all theme classes first
        document.body.classList.remove('light-theme', 'dark-theme');
        
        if (theme === 'light') {
            document.body.classList.add('light-theme');
        } else if (theme === 'dark') {
            document.body.classList.add('dark-theme');
        } else if (theme === 'auto') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyTheme(prefersDark ? 'dark' : 'light');
            return;
        }
        
        // Trigger any custom theme change events on the page
        const event = new CustomEvent('themeChanged', { detail: { theme: theme } });
        document.dispatchEvent(event);
    }

    // Apply saved theme when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        applyTheme(savedTheme);
    });

    // Listen for system theme changes when using 'auto' mode
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'auto') {
            applyTheme('auto');
        }
    });
</script>
