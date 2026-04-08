<?php
/**
 * CDF Management System — Function Loader
 *
 * This file is the single entry point required by all pages:
 *   require_once 'functions.php';
 *
 * It bootstraps the database connection and loads each domain module.
 * Previously a 6,600-line monolith; now split into focused modules under includes/.
 */

if (defined('CDF_FUNCTIONS_LOADED')) return;
define('CDF_FUNCTIONS_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php'; // Canonical Database class

// Global PDO connection used by all domain functions via `global $pdo`
// db.php may already have created $database — we just need $pdo
if (!isset($pdo)) {
    if (!isset($database)) {
        $database = new Database();
    }
    $pdo = $database->getConnection();
}

// ── Domain modules ────────────────────────────────────────────────────────────
require_once __DIR__ . '/includes/utils.functions.php';        // Validation, formatting, logging
require_once __DIR__ . '/includes/auth.functions.php';         // Auth, session, CSRF, login, password reset
require_once __DIR__ . '/includes/user.functions.php';         // User CRUD, registration, profiles
require_once __DIR__ . '/includes/project.functions.php';      // Projects, progress, expenses
require_once __DIR__ . '/includes/communication.functions.php'; // Notifications, messages
require_once __DIR__ . '/includes/evaluation.functions.php';   // Evaluations, compliance, quality, analytics
require_once __DIR__ . '/includes/settings.functions.php';     // System settings
require_once __DIR__ . '/includes/misc.functions.php';         // Remaining helpers
