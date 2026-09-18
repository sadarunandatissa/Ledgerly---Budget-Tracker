<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$userId  = current_user_id();
$summary = budget_summary($pdo, $userId);

// Everything this user has recorded, handed to the charts as JSON.
$stmt = $pdo->prepare(
    'SELECT id, type, category, amount, txn_date, description
     FROM expenses WHERE user_id = ? ORDER BY txn_date DESC, id DESC'
);
$stmt->execute([$userId]);
$entries = $stmt->fetchAll();

$recent = array_slice($entries, 0, 6);

// Largest single expense, used in the notes under the charts.
$biggest = null;
foreach ($entries as $row) {
    if ($row['type'] === 'expense' && (!$biggest || $row['amount'] > $biggest['amount'])) {
        $biggest = $row;
    }
}

$root        = '';
$activePage  = 'dashboard';
$pageTitle   = 'Dashboard';
$pageScripts = ['js/dashboard.js'];
require_once __DIR__ . '/includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script type="application/json" id="dashboardData"><?= json_encode($entries, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<section class="section-tight">
  <div class="container">

    <?= show_flash() ?>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="mb-1">Hello, <?= e($_SESSION['first_name']) ?></h1>
        <p class="text-muted mb-0">Here is where your money stands right now.</p>
      </div>
      <div class="btn-group" role="group" aria-label="Choose a period">
        <button class="btn btn-sm btn-accent"       type="button" data-period="0">All time</button>
        <button class="btn btn-sm btn-outline-ink"  type="button" data-period="90">90 days</button>
        <button class="btn btn-sm btn-outline-ink"  type="button" data-period="30">30 days</button>
        <button class="btn btn-sm btn-outline-ink"  type="button" data-period="7">7 days</button>
      </div>
    </div>

    <?php if (!$entries): ?>
      <div class="panel empty-state">
        <h2>Your book is empty</h2>
        <p>Record your first entry and the totals and charts on this page will fill themselves in.</p>
        <a class="btn btn-accent mt-2" href="add-transaction.php">Add an entry</a>
      </div>
    <?php else: ?>

    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="stat is-income">
          <p class="stat-label">Total income</p>
          <p class="stat-value num" data-total-income><?= money($summary['income']) ?></p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat is-spent">
          <p class="stat-label">Total spent</p>
          <p class="stat-value num" data-total-spent><?= money($summary['spent']) ?></p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat is-remaining">
          <p class="stat-label">Remaining</p>
          <p class="stat-value num" data-total-remaining><?= money($summary['remaining']) ?></p>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat is-count">
          <p class="stat-label">Entries recorded</p>
          <p class="stat-value num" data-total-entries><?= (int) $summary['entries'] ?></p>
        </div>
      </div>
    </div>

    <div class="panel mb-4">
      <div class="panel-head">
        <h2 class="h5 mb-0">Income against spending</h2>
        <span class="form-hint" id="spendNote"></span>
      </div>
      <div class="progress-rule" id="spendRule"><span style="width:0"></span></div>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="panel h-100">
          <div class="panel-head">
            <h2 class="h5 mb-0">Where the money goes</h2>
            <span class="form-hint" data-bs-toggle="tooltip"
                  title="Only expense entries are counted here.">By category</span>
          </div>
          <div class="chart-box"><canvas id="categoryChart" aria-label="Spending by category"></canvas></div>
          <ul class="chart-legend" id="categoryLegend"></ul>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="panel h-100">
          <div class="panel-head">
            <h2 class="h5 mb-0">Month by month</h2>
            <span class="form-hint">Last six months</span>
          </div>
          <div class="chart-box"><canvas id="monthlyChart" aria-label="Income and spending by month"></canvas></div>
          <?php if ($biggest): ?>
            <p class="form-hint mt-3 mb-0">
              Largest single expense so far: <?= money($biggest['amount']) ?>
              on <?= e($biggest['category']) ?>, <?= e($biggest['txn_date']) ?>.
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="panel mt-4">
      <div class="panel-head">
        <h2 class="h5 mb-0">Latest entries</h2>
        <a class="btn btn-sm btn-outline-ink" href="transactions.php">View all</a>
      </div>
      <div class="table-responsive">
        <table class="table table-ledger align-middle">
          <thead>
            <tr>
              <th scope="col">Date</th>
              <th scope="col">Type</th>
              <th scope="col">Category</th>
              <th scope="col">Description</th>
              <th scope="col" class="text-end">Amount</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($recent as $row): $in = $row['type'] === 'income'; ?>
            <tr>
              <td class="num"><?= e($row['txn_date']) ?></td>
              <td><span class="tag <?= $in ? 'tag-in' : 'tag-out' ?>"><?= $in ? 'Income' : 'Expense' ?></span></td>
              <td><?= e($row['category']) ?></td>
              <td><?= $row['description'] !== '' && $row['description'] !== null
                        ? e($row['description'])
                        : '<span class="text-muted">No note</span>' ?></td>
              <td class="amount text-end <?= $in ? 'amount-in' : 'amount-out' ?>">
                <?= $in ? '+' : '-' ?><?= money($row['amount']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
