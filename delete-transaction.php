<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// The user_id in the WHERE clause is what stops one account deleting another's row.
$stmt = $pdo->prepare('DELETE FROM expenses WHERE id = ? AND user_id = ?');
$stmt->execute([$id, current_user_id()]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Entry deleted.');
} else {
    set_flash('error', 'That entry was not found in your book.');
}

header('Location: transactions.php');
exit;
