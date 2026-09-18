<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: ../dashboard.php');
    exit;
}

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    }

    $old = [
        'first_name'  => clean($_POST['first_name']  ?? ''),
        'middle_name' => clean($_POST['middle_name'] ?? ''),
        'last_name'   => clean($_POST['last_name']   ?? ''),
        'address'     => clean($_POST['address']     ?? ''),
        'mobile'      => clean($_POST['mobile']      ?? ''),
        'username'    => clean($_POST['username']    ?? ''),
        'email'       => strtolower(clean($_POST['email'] ?? '')),
    ];
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($old['first_name'] === '')                    { $errors['first_name'] = 'Enter your first name.'; }
    if ($old['last_name'] === '')                     { $errors['last_name']  = 'Enter your last name.'; }
    if ($old['address'] === '')                       { $errors['address']    = 'Enter your address.'; }
    if (!valid_mobile($old['mobile']))                { $errors['mobile']     = 'Enter a valid mobile number.'; }
    if (!valid_username($old['username']))            { $errors['username']   = 'Use 4 to 20 letters, numbers or underscores.'; }
    if (!valid_email($old['email']))                  { $errors['email']      = 'Enter a valid email address.'; }
    if (strlen($password) < 8)                        { $errors['password']   = 'Use at least 8 characters.'; }
    elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Mix letters and numbers.';
    }
    if ($password !== $confirm)                       { $errors['confirm_password'] = 'The two passwords do not match.'; }

    if (!$errors) {
        $check = $pdo->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $check->execute([$old['username'], $old['email']]);
        foreach ($check as $row) {
            if (strcasecmp($row['username'], $old['username']) === 0) {
                $errors['username'] = 'That username is taken. Try another.';
            }
            if (strcasecmp($row['email'], $old['email']) === 0) {
                $errors['email'] = 'An account already uses that email address.';
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (first_name, middle_name, last_name, address, mobile, username, email, password)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $old['first_name'],
            $old['middle_name'] !== '' ? $old['middle_name'] : null,
            $old['last_name'],
            $old['address'],
            $old['mobile'],
            $old['username'],
            $old['email'],
            password_hash($password, PASSWORD_DEFAULT),
        ]);

        set_flash('success', 'Account created. Log in with your username and password.');
        header('Location: login.php');
        exit;
    }
}

$root       = '../';
$activePage = 'register';
$pageTitle  = 'Create an account';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="auth-wrap auth-wide">
      <h1 class="mb-2">Create an account</h1>
      <p class="text-muted mb-4">You will log in with the username you choose here.</p>

      <?= show_flash() ?>
      <?php if (!empty($errors['form'])): ?>
        <div class="alert alert-danger"><?= e($errors['form']) ?></div>
      <?php endif; ?>

      <div class="panel">
        <form action="register.php" method="post" novalidate data-validate>
          <?= csrf_field() ?>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="first_name">First name</label>
              <input class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                     type="text" id="first_name" name="first_name" data-rule="required|name"
                     value="<?= e($old['first_name'] ?? '') ?>" autocomplete="given-name" required>
              <span class="field-error" data-error-for="first_name"><?= e($errors['first_name'] ?? '') ?></span>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="middle_name">Middle name <span class="form-hint">(optional)</span></label>
              <input class="form-control" type="text" id="middle_name" name="middle_name"
                     value="<?= e($old['middle_name'] ?? '') ?>" autocomplete="additional-name">
            </div>

            <div class="col-md-4">
              <label class="form-label" for="last_name">Last name</label>
              <input class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                     type="text" id="last_name" name="last_name" data-rule="required|name"
                     value="<?= e($old['last_name'] ?? '') ?>" autocomplete="family-name" required>
              <span class="field-error" data-error-for="last_name"><?= e($errors['last_name'] ?? '') ?></span>
            </div>

            <div class="col-12">
              <label class="form-label" for="address">Address</label>
              <input class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                     type="text" id="address" name="address" data-rule="required"
                     value="<?= e($old['address'] ?? '') ?>" autocomplete="street-address" required>
              <span class="field-error" data-error-for="address"><?= e($errors['address'] ?? '') ?></span>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="mobile">Mobile number</label>
              <input class="form-control <?= isset($errors['mobile']) ? 'is-invalid' : '' ?>"
                     type="tel" id="mobile" name="mobile" data-rule="required|mobile"
                     value="<?= e($old['mobile'] ?? '') ?>" autocomplete="tel" required>
              <span class="field-error" data-error-for="mobile"><?= e($errors['mobile'] ?? '') ?></span>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="email">Email address</label>
              <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                     type="email" id="email" name="email" data-rule="required|email"
                     value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required>
              <span class="field-error" data-error-for="email"><?= e($errors['email'] ?? '') ?></span>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="username">Username</label>
              <input class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                     type="text" id="username" name="username" data-rule="required|username"
                     value="<?= e($old['username'] ?? '') ?>" autocomplete="username" required>
              <span class="field-error" data-error-for="username"><?= e($errors['username'] ?? '') ?></span>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="password">Password</label>
              <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                     type="password" id="password" name="password" data-rule="required|password"
                     autocomplete="new-password" required>
              <div class="strength-bar" data-strength-bar><span></span></div>
              <small class="form-hint" data-strength-text></small>
              <span class="field-error" data-error-for="password"><?= e($errors['password'] ?? '') ?></span>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="confirm_password">Confirm password</label>
              <input class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                     type="password" id="confirm_password" name="confirm_password"
                     data-rule="required|match" data-match-field="password"
                     autocomplete="new-password" required>
              <span class="field-error" data-error-for="confirm_password"><?= e($errors['confirm_password'] ?? '') ?></span>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
            <button class="btn btn-accent" type="submit">Create account</button>
            <span class="form-hint">Already registered? <a href="login.php">Log in</a></span>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
