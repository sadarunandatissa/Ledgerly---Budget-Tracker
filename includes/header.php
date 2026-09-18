<?php
/**
 * Shared page head + navigation.
 * Before including, set:
 *   $pageTitle  - text for the <title> tag
 *   $root       - relative path back to the project root ('' or '../')
 *   $activePage - file name used to highlight the current nav link
 */
require_once __DIR__ . '/functions.php';

$root       = $root       ?? '';
$pageTitle  = $pageTitle  ?? 'Ledgerly';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Ledgerly is a private budget book: record what you earn, record what you spend, and see where the month went.">
<title><?= e($pageTitle) ?> | Ledgerly</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= $root ?>css/style.css">
</head>
<body>

<a class="skip-link" href="#main">Skip to content</a>

<nav class="navbar navbar-expand-lg site-nav" id="siteNav">
  <div class="container">
    <a class="navbar-brand" href="<?= $root ?>index.php">
      <span class="brand-mark" aria-hidden="true"></span>Ledgerly
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Open navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link <?= $activePage === 'index' ? 'active' : '' ?>" href="<?= $root ?>index.php">Home</a>
        </li>
        <?php if (is_logged_in()): ?>
          <li class="nav-item">
            <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= $root ?>dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $activePage === 'transactions' ? 'active' : '' ?>" href="<?= $root ?>transactions.php">Transactions</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $activePage === 'add' ? 'active' : '' ?>" href="<?= $root ?>add-transaction.php">Add entry</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $activePage === 'profile' ? 'active' : '' ?>" href="<?= $root ?>profile.php">Profile</a>
          </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link <?= $activePage === 'contact' ? 'active' : '' ?>" href="<?= $root ?>contact.php">Contact</a>
        </li>
        <?php if (is_logged_in()): ?>
          <li class="nav-item ms-lg-3">
            <a class="btn btn-outline-ink btn-sm" href="<?= $root ?>auth/logout.php">Log out</a>
          </li>
        <?php else: ?>
          <li class="nav-item ms-lg-2">
            <a class="nav-link <?= $activePage === 'login' ? 'active' : '' ?>" href="<?= $root ?>auth/login.php">Log in</a>
          </li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-accent btn-sm" href="<?= $root ?>auth/register.php">Create account</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main id="main">
