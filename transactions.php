<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$userId = current_user_id();

// First load is rendered by PHP; later filtering happens through api/transactions.php.
$stmt = $pdo->prepare(
    'SELECT id, type, category, amount, txn_date, description
     FROM expenses WHERE user_id = ? ORDER BY txn_date DESC, id DESC'
);
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

$allCategories = array_merge(categories('expense'), categories('income'));

$root        = '';
$activePage  = 'transactions';
$pageTitle   = 'Transactions';
$pageScripts = ['js/transactions.js'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-tight">
  <div class="container">

    <?= show_flash() ?>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="mb-1">Transaction history</h1>
        <p class="text-muted mb-0">Every entry on your account, newest first.</p>
      </div>
      <a class="btn btn-accent" href="add-transaction.php">Add an entry</a>
    </div>

    <div class="filter-bar mb-3">
      <form id="filterForm" class="row g-3 align-items-end">
        <div class="col-12 col-lg-4">
          <label class="form-label" for="searchBox">Search</label>
          <input class="form-control" type="search" id="searchBox"
                 placeholder="Category, note or date" autocomplete="off">
          <small class="form-hint">Filters the rows below as you type.</small>
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label" for="type">Type</label>
          <select class="form-select" id="type" name="type">
            <option value="">All</option>
            <option value="expense">Expense</option>
            <option value="income">Income</option>
          </select>
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label" for="categoryFilter">Category</label>
          <select class="form-select" id="categoryFilter" name="category">
            <option value="">All</option>
            <?php foreach ($allCategories as $category): ?>
              <option value="<?= e($category) ?>"><?= e($category) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label" for="from">From</label>
          <input class="form-control" type="date" id="from" name="from">
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label" for="to">To</label>
          <input class="form-control" type="date" id="to" name="to">
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label" for="min">Min amount</label>
          <input class="form-control" type="number" step="0.01" min="0" id="min" name="min">
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label" for="max">Max amount</label>
          <input class="form-control" type="number" step="0.01" min="0" id="max" name="max">
        </div>

        <div class="col-12 col-lg-8 d-flex flex-wrap gap-2">
          <button class="btn btn-accent" type="submit">Apply filters</button>
          <button class="btn btn-outline-ink" type="button" id="resetFilters">Clear</button>
        </div>
      </form>
    </div>

    <div class="panel">
      <div class="panel-head">
        <span class="result-count" id="resultCount"></span>
        <span class="result-count">Net of the rows shown: <span class="legend-value" id="resultSum">0.00</span></span>
      </div>

      <div class="table-responsive">
        <table class="table table-ledger align-middle" id="txnTable">
          <thead>
            <tr>
              <th scope="col" data-sort="date">Date</th>
              <th scope="col" data-sort="type">Type</th>
              <th scope="col" data-sort="category">Category</th>
              <th scope="col">Description</th>
              <th scope="col" class="text-end" data-sort="amount">Amount</th>
              <th scope="col" class="text-end">Action</th>
            </tr>
          </thead>
          <tbody id="txnBody">
            <?php foreach ($rows as $row): $in = $row['type'] === 'income'; ?>
              <tr data-row
                  data-amount="<?= e($row['amount']) ?>"
                  data-type="<?= e($row['type']) ?>"
                  data-category="<?= e($row['category']) ?>"
                  data-date="<?= e($row['txn_date']) ?>"
                  data-search="<?= e(strtolower($row['category'] . ' ' . (string) $row['description'] . ' ' . $row['txn_date'] . ' ' . $row['type'])) ?>">
                <td class="num"><?= e($row['txn_date']) ?></td>
                <td><span class="tag <?= $in ? 'tag-in' : 'tag-out' ?>"><?= $in ? 'Income' : 'Expense' ?></span></td>
                <td><?= e($row['category']) ?></td>
                <td><?= $row['description'] !== '' && $row['description'] !== null
                          ? e($row['description'])
                          : '<span class="text-muted">No note</span>' ?></td>
                <td class="amount text-end <?= $in ? 'amount-in' : 'amount-out' ?>">
                  <?= $in ? '+' : '-' ?><?= money($row['amount']) ?>
                </td>
                <td class="text-end text-nowrap">
                  <a class="btn btn-sm btn-outline-ink" href="add-transaction.php?id=<?= (int) $row['id'] ?>">Edit</a>
                  <a class="btn btn-sm btn-outline-ink" href="delete-transaction.php?id=<?= (int) $row['id'] ?>"
                     onclick="return confirm('Delete this entry? This cannot be undone.');">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr id="emptyRow" hidden>
              <td colspan="6">
                <div class="empty-state">
                  <p class="mb-2">No entry matches what you are looking for.</p>
                  <p class="mb-0">Clear the filters, or <a href="add-transaction.php">record a new entry</a>.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
