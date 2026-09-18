<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: ../dashboard.php');
    exit;
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter both your username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, first_name, password FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = (int) $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];

            set_flash('success', 'Welcome back, ' . $user['first_name'] . '.');
            header('Location: ../dashboard.php');
            exit;
        }

        // Same message either way, so the form cannot be used to discover usernames.
        $error = 'That username and password combination did not match an account.';
    }
}

$root       = '../';
$activePage = 'login';
$pageTitle  = 'Log in';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="auth-wrap">
      <h1 class="mb-2">Log in</h1>
      <p class="text-muted mb-4">Use the username you picked when you signed up.</p>

      <?= show_flash() ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <div class="panel">
        <form action="login.php" method="post" novalidate data-validate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" type="text" id="username" name="username"
                   data-rule="required|username" value="<?= e($username) ?>"
                   autocomplete="username" autofocus required>
            <span class="field-error" data-error-for="username"></span>
          </div>

          <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password"
                   data-rule="required" autocomplete="current-password" required>
            <span class="field-error" data-error-for="password"></span>
          </div>

          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="showPassword">
            <label class="form-check-label" for="showPassword">Show password</label>
          </div>

          <button class="btn btn-accent w-100" type="submit">Log in</button>
        </form>
      </div>

      <p class="text-center mt-3 form-hint">
        No account yet? <a href="register.php">Create one</a>
      </p>
    </div>
  </div>
</section>

<script>
  document.getElementById('showPassword').addEventListener('change', function () {
    var field = document.getElementById('password');
    field.type = this.checked ? 'text' : 'password';
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
