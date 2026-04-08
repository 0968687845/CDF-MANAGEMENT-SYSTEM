<?php
require_once 'functions.php';

function checkTable($pdo, $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return $stmt->fetchColumn() > 0;
}

function getColumns($pdo, $table) {
    $stmt = $pdo->prepare("SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$requiredTables = ['users', 'beneficiary_groups', 'group_members'];
$missing = [];

echo "Database schema check for '" . DB_NAME . "'\n\n";

foreach ($requiredTables as $t) {
    $exists = checkTable($pdo, $t);
    echo "Table $t: " . ($exists ? "FOUND" : "MISSING") . "\n";
    if ($exists) {
        $cols = getColumns($pdo, $t);
        foreach ($cols as $c) {
            echo "  - " . $c['COLUMN_NAME'] . " (" . $c['COLUMN_TYPE'] . ")\n";
        }
        echo "\n";
    } else {
        $missing[] = $t;
    }
}

// Quick check for users meta column
if (checkTable($pdo, 'users')) {
    $cols = array_column(getColumns($pdo, 'users'), 'COLUMN_NAME');
    echo "\nUsers columns present: " . implode(', ', $cols) . "\n";
    echo "\nChecking required users columns...\n";
    $requiredUserCols = ['username','email','password','first_name','last_name','nrc','phone','role','constituency','department','status','meta'];
    foreach ($requiredUserCols as $col) {
        echo " - $col: " . (in_array($col, $cols) ? 'OK' : 'MISSING') . "\n";
    }
}

if (!empty($missing)) {
    echo "\nMissing tables detected. Run setup_database.php to create missing tables.\n";
} else {
    echo "\nAll required tables exist.\n";
}

echo "\nIf you still see 'An error occurred during registration. Please try again.' run 'php test_register.php' and paste the exact output.\n";
