<?php
require_once 'functions.php';

// Small test script for registerUser()
function printResult($label, $result) {
    echo "== $label ==\n";
    if ($result === true) {
        echo "Success\n";
    } else {
        echo "Error: " . $result . "\n";
    }
    echo "\n";
}

// Make sure DB exists and setup_database.php has been run
echo "Note: Ensure you've run setup_database.php and XAMPP MySQL is running.\n\n";

// Unique suffix to avoid collisions
$suffix = time();

// Single beneficiary test
$single = [
    'username' => 'testuser_' . $suffix,
    'email' => 'testuser' . $suffix . '@example.com',
    'password' => 'Test1234!',
    'first_name' => 'Test',
    'last_name' => 'User',
    'nrc' => '123456/78/9',
    'phone' => '0977000001',
    'role' => 'beneficiary',
    'constituency' => 'Lusaka Central',
    'department' => ''
];

$resultSingle = registerUser($single);
printResult('Single registration', $resultSingle);

// Group beneficiary test
$group = [
    'username' => 'groupuser_' . $suffix,
    'email' => 'groupuser' . $suffix . '@example.com',
    'password' => 'Test1234!',
    'first_name' => 'Group',
    'last_name' => 'Owner',
    'nrc' => '223456/78/1',
    'phone' => '0977000002',
    'role' => 'beneficiary',
    'constituency' => 'Ndola Central',
    'department' => '',
    'is_group' => 1,
    'group_name' => 'Test Group ' . $suffix,
    'members' => [
        [
            'name' => 'Member One',
            'phone' => '0977000101',
            'nrc' => '323456/78/2'
        ],
        [
            'name' => 'Member Two',
            'phone' => '0977000102',
            'nrc' => '423456/78/3'
        ]
    ]
];

$resultGroup = registerUser($group);
printResult('Group registration', $resultGroup);

echo "Done.\n";
