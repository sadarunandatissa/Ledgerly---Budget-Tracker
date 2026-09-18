<?php
/**
 * Shared helper functions used across the whole application.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Trim and collapse whitespace on any raw input value. */
function clean(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value));
}

/** Escape a value before printing it into HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** True when somebody is logged in. */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/** Send guests to the login page. */
function require_login(string $prefix = ''): void
{
    if (!is_logged_in()) {
        header('Location: ' . $prefix . 'auth/login.php');
        exit;
    }
}

/** Current user id, or 0 for guests. */
function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

/** Generate / reuse a CSRF token for this session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden CSRF field for forms. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verify a posted CSRF token. */
function csrf_valid(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/** Store a one-time message shown on the next page load. */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Print and clear the flash message. */
function show_flash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $class = $flash['type'] === 'success' ? 'alert-success' : 'alert-danger';
    return '<div class="alert ' . $class . '" role="alert">' . e($flash['message']) . '</div>';
}

/** Validation helpers. */
function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function valid_mobile(string $mobile): bool
{
    return (bool) preg_match('/^[0-9+\s-]{9,15}$/', $mobile);
}

function valid_username(string $username): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_]{4,20}$/', $username);
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/** Money formatting used by every page. */
function money($amount): string
{
    return number_format((float) $amount, 2);
}

/** Categories offered in the entry form. */
function categories(string $type = 'expense'): array
{
    if ($type === 'income') {
        return ['Salary', 'Freelance', 'Business', 'Investment', 'Gift', 'Other income'];
    }
    return ['Food', 'Rent', 'Transport', 'Utilities', 'Entertainment', 'Health', 'Education', 'Shopping', 'Other'];
}

/** Totals for the signed-in user. */
function budget_summary(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN type = 'income'  THEN amount END), 0) AS income,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount END), 0) AS spent,
            COUNT(*) AS entries
         FROM expenses WHERE user_id = ?"
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch() ?: ['income' => 0, 'spent' => 0, 'entries' => 0];
    $row['remaining'] = (float) $row['income'] - (float) $row['spent'];
    return $row;
}
