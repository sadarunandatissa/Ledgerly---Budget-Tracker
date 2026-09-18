<?php
/**
 * Creates a demo account with three months of sample entries.
 * Run it once from the browser, then delete this file.
 *
 *   username: demo
 *   password: demo1234
 */
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

$check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$check->execute(['demo']);

if ($row = $check->fetch()) {
    exit("The demo account already exists (user id {$row['id']}). Nothing to do.\n");
}

$pdo->prepare(
    'INSERT INTO users (first_name, middle_name, last_name, address, mobile, username, email, password)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
)->execute([
    'Nadeesha', 'Kumari', 'Perera',
    '48 Maithripala Senanayake Mawatha, Anuradhapura',
    '0771234567', 'demo', 'demo@ledgerly.test',
    password_hash('demo1234', PASSWORD_DEFAULT),
]);

$userId = (int) $pdo->lastInsertId();

// [days ago, type, category, amount, description]
$samples = [
    [75, 'income',  'Salary',        185000, 'Monthly salary'],
    [74, 'expense', 'Rent',           62000, 'Apartment rent'],
    [70, 'expense', 'Food',            8450, 'Weekly market run'],
    [66, 'expense', 'Transport',       4200, 'Fuel'],
    [60, 'expense', 'Utilities',       7300, 'Electricity and water'],
    [45, 'income',  'Salary',        185000, 'Monthly salary'],
    [41, 'income',  'Freelance',      32000, 'Logo design project'],
    [44, 'expense', 'Rent',           62000, 'Apartment rent'],
    [40, 'expense', 'Food',            9600, 'Groceries'],
    [38, 'expense', 'Entertainment',   3500, 'Cinema and dinner'],
    [35, 'expense', 'Health',         12500, 'Dental check-up'],
    [33, 'expense', 'Shopping',       15800, 'Winter jacket'],
    [15, 'income',  'Salary',        185000, 'Monthly salary'],
    [14, 'expense', 'Rent',           62000, 'Apartment rent'],
    [10, 'expense', 'Food',            7250, 'Groceries'],
    [ 8, 'expense', 'Transport',       5100, 'Bus pass and fuel'],
    [ 6, 'expense', 'Education',      22000, 'Online course'],
    [ 4, 'expense', 'Utilities',       6900, 'Internet and phone'],
    [ 2, 'expense', 'Food',            3150, 'Lunches'],
    [ 1, 'expense', 'Entertainment',   2400, 'Streaming subscription'],
];

$insert = $pdo->prepare(
    'INSERT INTO expenses (user_id, type, category, amount, txn_date, description)
     VALUES (?, ?, ?, ?, ?, ?)'
);

foreach ($samples as [$daysAgo, $type, $category, $amount, $note]) {
    $insert->execute([
        $userId,
        $type,
        $category,
        $amount,
        date('Y-m-d', strtotime('-' . $daysAgo . ' days')),
        $note,
    ]);
}

echo "Demo account created.\n";
echo "  username: demo\n";
echo "  password: demo1234\n";
echo count($samples) . " sample entries added.\n\n";
echo "Delete seed-demo.php now that it has run.\n";
