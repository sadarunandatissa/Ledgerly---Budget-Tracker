<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$root       = '';
$activePage = 'index';
$pageTitle  = 'A private budget book';

// A small live figure for the hero: how many entries this account holds.
$summary = is_logged_in() ? budget_summary($pdo, current_user_id()) : null;

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <div class="row align-items-center gy-5">
      <div class="col-lg-6">
        <?= show_flash() ?>
        <h1>Know where the month went.</h1>
        <p class="hero-lead mt-3">
          Ledgerly is a budget book you keep on your own account. Write down what comes in,
          write down what goes out, and the running balance takes care of itself.
        </p>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <?php if (is_logged_in()): ?>
            <a class="btn btn-accent" href="dashboard.php">Open my dashboard</a>
            <a class="btn btn-outline-ink" href="add-transaction.php">Add an entry</a>
          <?php else: ?>
            <a class="btn btn-accent" href="auth/register.php">Create an account</a>
            <a class="btn btn-outline-ink" href="#how-it-works">See how it works</a>
          <?php endif; ?>
        </div>
        <p class="form-hint mt-3 mb-0">No card, no sharing. Your figures are visible only after you log in.</p>
      </div>

      <div class="col-lg-6">
        <div class="ledger-sheet">
          <header>
            <span><?= is_logged_in() ? 'Your book' : 'March, a sample book' ?></span>
            <span>Amount</span>
          </header>
          <?php if ($summary && $summary['entries'] > 0):
              $recent = $pdo->prepare(
                  'SELECT type, category, amount FROM expenses WHERE user_id = ? ORDER BY txn_date DESC, id DESC LIMIT 4'
              );
              $recent->execute([current_user_id()]);
              foreach ($recent as $row): ?>
                <div class="ledger-row">
                  <span><?= e($row['category']) ?></span>
                  <span class="<?= $row['type'] === 'income' ? 'in' : 'out' ?>">
                    <?= $row['type'] === 'income' ? '+' : '-' ?><?= money($row['amount']) ?>
                  </span>
                </div>
              <?php endforeach; ?>
              <div class="ledger-total">
                <span>Remaining</span>
                <span><?= money($summary['remaining']) ?></span>
              </div>
          <?php else: ?>
            <div class="ledger-row"><span>Salary</span><span class="in">+185,000.00</span></div>
            <div class="ledger-row"><span>Rent</span><span class="out">-62,000.00</span></div>
            <div class="ledger-row"><span>Food</span><span class="out">-24,350.00</span></div>
            <div class="ledger-row"><span>Transport</span><span class="out">-9,800.00</span></div>
            <div class="ledger-total"><span>Remaining</span><span>88,850.00</span></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section" id="how-it-works">
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-4 reveal">
        <div class="feature">
          <h3>Your account, your figures</h3>
          <p>Passwords are hashed before they are stored, and every entry is tied to your user id,
             so one account can never read another's book.</p>
        </div>
      </div>
      <div class="col-lg-4 reveal">
        <div class="feature">
          <h3>One screen for the whole picture</h3>
          <p>Income, spending and what is left are added up for you, with a breakdown by category
             and a six-month comparison you can read at a glance.</p>
        </div>
      </div>
      <div class="col-lg-4 reveal">
        <div class="feature">
          <h3>Find any entry in seconds</h3>
          <p>Search as you type, narrow by category, date range or amount, and sort any column.
             The table updates in place.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-tight">
  <div class="container">
    <h2 class="mb-4">A quick tour</h2>
    <div class="slider" id="tourSlider" aria-roledescription="carousel" aria-label="Product tour">
      <div class="slides">
        <article class="slide is-active">
          <p class="slide-figure">01</p>
          <h3>Record an entry</h3>
          <p>Choose income or expense, type the amount, pick the date and category, add a note if it
             helps you remember. The form checks each field before it is sent.</p>
        </article>
        <article class="slide">
          <p class="slide-figure">02</p>
          <h3>Watch the balance move</h3>
          <p>The dashboard adds up everything you have recorded and shows what is left. Switch the
             period to the last week, month or quarter and the charts redraw instantly.</p>
        </article>
        <article class="slide">
          <p class="slide-figure">03</p>
          <h3>See the shape of your spending</h3>
          <p>A ring chart splits spending across categories with the share of the total next to each,
             and a bar chart puts income beside spending month by month.</p>
        </article>
        <article class="slide">
          <p class="slide-figure">04</p>
          <h3>Look back over the history</h3>
          <p>Filter by category, date range or amount, search the descriptions, and sort by date or
             value to find the one entry you are after.</p>
        </article>
      </div>
      <div class="slider-controls">
        <button class="slider-btn" type="button" data-slide-prev>Previous</button>
        <div class="slider-dots"></div>
        <button class="slider-btn" type="button" data-slide-next>Next</button>
      </div>
    </div>
  </div>
</section>

<section class="section-tight pb-5">
  <div class="container">
    <div class="panel d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <h2 class="mb-1">Start your book</h2>
        <p class="mb-0 text-muted">It takes a minute to sign up and one entry to be useful.</p>
      </div>
      <a class="btn btn-ledger" href="<?= is_logged_in() ? 'add-transaction.php' : 'auth/register.php' ?>">
        <?= is_logged_in() ? 'Add an entry' : 'Create an account' ?>
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
