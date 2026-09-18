<?php
/**
 * Returns the signed-in user's transactions as JSON.
 * Used by js/transactions.js so the filter bar never reloads the page.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Log in to view your transactions.']);
    exit;
}

$userId = current_user_id();

$sql    = 'SELECT id, type, category, amount, txn_date, description
           FROM expenses WHERE user_id = ?';
$params = [$userId];

$type = $_GET['type'] ?? '';
if ($type === 'income' || $type === 'expense') {
    $sql     .= ' AND type = ?';
    $params[] = $type;
}

$category = clean($_GET['category'] ?? '');
if ($category !== '') {
    $sql     .= ' AND category = ?';
    $params[] = $category;
}

$from = clean($_GET['from'] ?? '');
if ($from !== '' && valid_date($from)) {
    $sql     .= ' AND txn_date >= ?';
    $params[] = $from;
}

$to = clean($_GET['to'] ?? '');
if ($to !== '' && valid_date($to)) {
    $sql     .= ' AND txn_date <= ?';
    $params[] = $to;
}

if (isset($_GET['min']) && is_numeric($_GET['min'])) {
    $sql     .= ' AND amount >= ?';
    $params[] = (float) $_GET['min'];
}

if (isset($_GET['max']) && is_numeric($_GET['max']) && (float) $_GET['max'] > 0) {
    $sql     .= ' AND amount <= ?';
    $params[] = (float) $_GET['max'];
}

$sql .= ' ORDER BY txn_date DESC, id DESC LIMIT 500';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Escape here so the browser can insert the values straight into the table.
$transactions = array_map(function (array $row): array {
    return [
        'id'          => (int) $row['id'],
        'type'        => $row['type'],
        'category'    => e($row['category']),
        'amount'      => (float) $row['amount'],
        'txn_date'    => $row['txn_date'],
        'description' => e((string) $row['description']),
    ];
}, $rows);

$income = 0.0;
$spent  = 0.0;
foreach ($transactions as $row) {
    if ($row['type'] === 'income') { $income += $row['amount']; } else { $spent += $row['amount']; }
}

echo json_encode([
    'count'        => count($transactions),
    'income'       => round($income, 2),
    'spent'        => round($spent, 2),
    'net'          => round($income - $spent, 2),
    'transactions' => $transactions,
], JSON_UNESCAPED_UNICODE);
