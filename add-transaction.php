<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$userId = current_user_id();
$errors = [];

// Editing an existing entry when ?id= is present and the row belongs to this user.
$editing = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM expenses WHERE id = ? AND user_id = ?');
    $stmt->execute([(int) $_GET['id'], $userId]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) {
        set_flash('error', 'That entry was not found in your book.');
        header('Location: transactions.php');
        exit;
    }
}

$form = [
    'type'        => $editing['type']        ?? 'expense',
    'amount'      => $editing['amount']      ?? '',
    'txn_date'    => $editing['txn_date']    ?? date('Y-m-d'),
    'category'    => $editing['category']    ?? '',
    'description' => (string) ($editing['description'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $form = [
        'type'        => ($_POST['type'] ?? '') === 'income' ? 'income' : 'expense',
        'amount'      => clean($_POST['amount'] ?? ''),
        'txn_date'    => clean($_POST['txn_date'] ?? ''),
        'category'    => clean($_POST['category'] ?? ''),
        'description' => clean($_POST['description'] ?? ''),
    ];
    $entryId = isset($_POST['entry_id']) ? (int) $_POST['entry_id'] : 0;

    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    }
    if (!is_numeric($form['amount']) || (float) $form['amount'] <= 0) {
        $errors['amount'] = 'Enter an amount greater than zero.';
    } elseif ((float) $form['amount'] > 99999999) {
        $errors['amount'] = 'That amount is too large.';
    }
    if (!valid_date($form['txn_date'])) {
        $errors['txn_date'] = 'Pick a valid date.';
    } elseif ($form['txn_date'] > date('Y-m-d')) {
        $errors['txn_date'] = 'The date cannot be in the future.';
    }
    if (!in_array($form['category'], categories($form['type']), true)) {
        $errors['category'] = 'Choose a category from the list.';
    }
    if (mb_strlen($form['description']) > 255) {
        $errors['description'] = 'Keep the note under 255 characters.';
    }

    if (!$errors) {
        if ($entryId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE expenses SET type = ?, amount = ?, txn_date = ?, category = ?, description = ?
                 WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([
                $form['type'], $form['amount'], $form['txn_date'],
                $form['category'], $form['description'], $entryId, $userId,
            ]);
            set_flash('success', 'Entry updated.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO expenses (user_id, type, amount, txn_date, category, description)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId, $form['type'], $form['amount'],
                $form['txn_date'], $form['category'], $form['description'],
            ]);
            set_flash('success', 'Entry saved to your book.');
        }
        header('Location: transactions.php');
        exit;
    }
}

$root       = '';
$activePage = 'add';
$pageTitle  = $editing ? 'Edit entry' : 'Add entry';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="auth-wrap">
      <h1 class="mb-2"><?= $editing ? 'Edit this entry' : 'Add an entry' ?></h1>
      <p class="text-muted mb-4">Record money coming in or money going out. Everything here stays on your account.</p>

      <?php if (!empty($errors['form'])): ?>
        <div class="alert alert-danger"><?= e($errors['form']) ?></div>
      <?php endif; ?>

      <div class="panel">
        <form action="add-transaction.php<?= $editing ? '?id=' . (int) $editing['id'] : '' ?>"
              method="post" novalidate data-validate>
          <?= csrf_field() ?>
          <?php if ($editing): ?>
            <input type="hidden" name="entry_id" value="<?= (int) $editing['id'] ?>">
          <?php endif; ?>

          <fieldset class="mb-3">
            <legend class="form-label">Entry type</legend>
            <div class="btn-group w-100" role="group">
              <input class="btn-check" type="radio" name="type" id="typeExpense" value="expense"
                     <?= $form['type'] === 'expense' ? 'checked' : '' ?>>
              <label class="btn btn-outline-ink" for="typeExpense">Expense</label>

              <input class="btn-check" type="radio" name="type" id="typeIncome" value="income"
                     <?= $form['type'] === 'income' ? 'checked' : '' ?>>
              <label class="btn btn-outline-ink" for="typeIncome">Income</label>
            </div>
          </fieldset>

          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label" for="amount">Amount</label>
              <input class="form-control <?= isset($errors['amount']) ? 'is-invalid' : '' ?>"
                     type="number" step="0.01" min="0.01" id="amount" name="amount"
                     data-rule="required|amount" value="<?= e($form['amount']) ?>" required>
              <span class="field-error" data-error-for="amount"><?= e($errors['amount'] ?? '') ?></span>
            </div>

            <div class="col-sm-6">
              <label class="form-label" for="txn_date">Date</label>
              <input class="form-control <?= isset($errors['txn_date']) ? 'is-invalid' : '' ?>"
                     type="date" id="txn_date" name="txn_date" max="<?= date('Y-m-d') ?>"
                     data-rule="required|date" value="<?= e($form['txn_date']) ?>" required>
              <span class="field-error" data-error-for="txn_date"><?= e($errors['txn_date'] ?? '') ?></span>
            </div>

            <div class="col-12">
              <label class="form-label" for="category">Category</label>
              <select class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>"
                      id="category" name="category" data-rule="required" required>
                <option value="">Choose a category</option>
              </select>
              <span class="field-error" data-error-for="category"><?= e($errors['category'] ?? '') ?></span>
            </div>

            <div class="col-12">
              <label class="form-label" for="description">Note <span class="form-hint">(optional)</span></label>
              <textarea class="form-control" id="description" name="description" rows="3"
                        maxlength="255" placeholder="What was this for?"><?= e($form['description']) ?></textarea>
              <small class="form-hint"><span id="charCount">0</span> of 255 characters</small>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-4">
            <button class="btn btn-accent" type="submit"><?= $editing ? 'Save changes' : 'Save entry' ?></button>
            <a class="btn btn-outline-ink" href="transactions.php">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<script>
  /* The category list depends on the entry type, so it is rebuilt whenever
     the type changes. Dynamic content update, no page reload. */
  var categorySets = <?= json_encode([
      'expense' => categories('expense'),
      'income'  => categories('income'),
  ]) ?>;
  var chosenCategory = <?= json_encode($form['category']) ?>;

  var categorySelect = document.getElementById('category');

  function fillCategories() {
    var type = document.querySelector('input[name="type"]:checked').value;
    var list = categorySets[type];
    categorySelect.innerHTML = '<option value="">Choose a category</option>';
    list.forEach(function (name) {
      var option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      if (name === chosenCategory) { option.selected = true; }
      categorySelect.appendChild(option);
    });
  }

  document.querySelectorAll('input[name="type"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      chosenCategory = '';
      fillCategories();
    });
  });
  fillCategories();

  var note = document.getElementById('description');
  var counter = document.getElementById('charCount');
  function countChars() { counter.textContent = note.value.length; }
  note.addEventListener('input', countChars);
  countChars();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
