<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$userId  = current_user_id();
$errors  = [];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'details') {

    $data = [
        'first_name'  => clean($_POST['first_name']  ?? ''),
        'middle_name' => clean($_POST['middle_name'] ?? ''),
        'last_name'   => clean($_POST['last_name']   ?? ''),
        'address'     => clean($_POST['address']     ?? ''),
        'mobile'      => clean($_POST['mobile']      ?? ''),
        'email'       => strtolower(clean($_POST['email'] ?? '')),
    ];

    if (!csrf_valid())                      { $errors['form']       = 'Your session expired. Please try again.'; }
    if ($data['first_name'] === '')         { $errors['first_name'] = 'Enter your first name.'; }
    if ($data['last_name'] === '')          { $errors['last_name']  = 'Enter your last name.'; }
    if ($data['address'] === '')            { $errors['address']    = 'Enter your address.'; }
    if (!valid_mobile($data['mobile']))     { $errors['mobile']     = 'Enter a valid mobile number.'; }
    if (!valid_email($data['email']))       { $errors['email']      = 'Enter a valid email address.'; }

    if (!$errors) {
        $taken = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $taken->execute([$data['email'], $userId]);
        if ($taken->fetch()) {
            $errors['email'] = 'Another account already uses that email address.';
        }
    }

    if (!$errors) {
        $update = $pdo->prepare(
            'UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, address = ?, mobile = ?, email = ?
             WHERE id = ?'
        );
        $update->execute([
            $data['first_name'],
            $data['middle_name'] !== '' ? $data['middle_name'] : null,
            $data['last_name'],
            $data['address'],
            $data['mobile'],
            $data['email'],
            $userId,
        ]);
        $_SESSION['first_name'] = $data['first_name'];
        set_flash('success', 'Profile updated.');
        header('Location: profile.php');
        exit;
    }

    $user = array_merge($user, $data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_new_password'] ?? '';

    if (!csrf_valid()) {
        $errors['password_form'] = 'Your session expired. Please try again.';
    } elseif (!password_verify($current, $user['password'])) {
        $errors['current_password'] = 'That is not your current password.';
    } elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
        $errors['new_password'] = 'Use at least 8 characters, mixing letters and numbers.';
    } elseif ($new !== $confirm) {
        $errors['confirm_new_password'] = 'The two passwords do not match.';
    } else {
        $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
        set_flash('success', 'Password changed.');
        header('Location: profile.php');
        exit;
    }
}

$summary = budget_summary($pdo, $userId);

$root       = '';
$activePage = 'profile';
$pageTitle  = 'Profile';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-tight">
  <div class="container">
    <?= show_flash() ?>

    <h1 class="mb-1">Your profile</h1>
    <p class="text-muted mb-4">Account opened on <?= e(date('j F Y', strtotime($user['created_at']))) ?>.</p>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="panel mb-4">
          <h2 class="h6 text-muted mb-3">Account at a glance</h2>
          <p class="stat-label mb-1">Username</p>
          <p class="fw-semibold mb-3"><?= e($user['username']) ?></p>
          <p class="stat-label mb-1">Entries recorded</p>
          <p class="fw-semibold num mb-3"><?= (int) $summary['entries'] ?></p>
          <p class="stat-label mb-1">Remaining budget</p>
          <p class="stat-value num mb-0"><?= money($summary['remaining']) ?></p>
        </div>

        <div class="panel">
          <h2 class="h6 mb-2">Change your password</h2>
          <?php if (!empty($errors['password_form'])): ?>
            <div class="alert alert-danger"><?= e($errors['password_form']) ?></div>
          <?php endif; ?>

          <button class="btn btn-outline-ink btn-sm" type="button"
                  data-toggle-target="#passwordPanel" aria-expanded="false"
                  data-label-open="Hide" data-label-closed="Show">Show</button>

          <div id="passwordPanel" class="mt-3" <?= isset($errors['current_password']) || isset($errors['new_password']) || isset($errors['confirm_new_password']) ? '' : 'hidden' ?>>
            <form action="profile.php" method="post" novalidate data-validate>
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="password">

              <div class="mb-3">
                <label class="form-label" for="current_password">Current password</label>
                <input class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                       type="password" id="current_password" name="current_password"
                       data-rule="required" autocomplete="current-password" required>
                <span class="field-error" data-error-for="current_password"><?= e($errors['current_password'] ?? '') ?></span>
              </div>

              <div class="mb-3">
                <label class="form-label" for="new_password">New password</label>
                <input class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                       type="password" id="new_password" name="new_password"
                       data-rule="required|password" autocomplete="new-password" required>
                <span class="field-error" data-error-for="new_password"><?= e($errors['new_password'] ?? '') ?></span>
              </div>

              <div class="mb-3">
                <label class="form-label" for="confirm_new_password">Confirm new password</label>
                <input class="form-control <?= isset($errors['confirm_new_password']) ? 'is-invalid' : '' ?>"
                       type="password" id="confirm_new_password" name="confirm_new_password"
                       data-rule="required|match" data-match-field="new_password"
                       autocomplete="new-password" required>
                <span class="field-error" data-error-for="confirm_new_password"><?= e($errors['confirm_new_password'] ?? '') ?></span>
              </div>

              <button class="btn btn-accent btn-sm" type="submit">Change password</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="panel">
          <h2 class="h5 mb-3">Your details</h2>
          <?php if (!empty($errors['form'])): ?>
            <div class="alert alert-danger"><?= e($errors['form']) ?></div>
          <?php endif; ?>

          <form action="profile.php" method="post" novalidate data-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="details">

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label" for="first_name">First name</label>
                <input class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                       type="text" id="first_name" name="first_name" data-rule="required|name"
                       value="<?= e($user['first_name']) ?>" required>
                <span class="field-error" data-error-for="first_name"><?= e($errors['first_name'] ?? '') ?></span>
              </div>

              <div class="col-md-4">
                <label class="form-label" for="middle_name">Middle name <span class="form-hint">(optional)</span></label>
                <input class="form-control" type="text" id="middle_name" name="middle_name"
                       value="<?= e($user['middle_name'] ?? '') ?>">
              </div>

              <div class="col-md-4">
                <label class="form-label" for="last_name">Last name</label>
                <input class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                       type="text" id="last_name" name="last_name" data-rule="required|name"
                       value="<?= e($user['last_name']) ?>" required>
                <span class="field-error" data-error-for="last_name"><?= e($errors['last_name'] ?? '') ?></span>
              </div>

              <div class="col-12">
                <label class="form-label" for="address">Address</label>
                <input class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                       type="text" id="address" name="address" data-rule="required"
                       value="<?= e($user['address']) ?>" required>
                <span class="field-error" data-error-for="address"><?= e($errors['address'] ?? '') ?></span>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="mobile">Mobile number</label>
                <input class="form-control <?= isset($errors['mobile']) ? 'is-invalid' : '' ?>"
                       type="tel" id="mobile" name="mobile" data-rule="required|mobile"
                       value="<?= e($user['mobile']) ?>" required>
                <span class="field-error" data-error-for="mobile"><?= e($errors['mobile'] ?? '') ?></span>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       type="email" id="email" name="email" data-rule="required|email"
                       value="<?= e($user['email']) ?>" required>
                <span class="field-error" data-error-for="email"><?= e($errors['email'] ?? '') ?></span>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="usernameField">Username</label>
                <input class="form-control" type="text" id="usernameField"
                       value="<?= e($user['username']) ?>" disabled>
                <small class="form-hint">Your username cannot be changed.</small>
              </div>
            </div>

            <button class="btn btn-accent mt-4" type="submit">Save changes</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
