<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$old    = ['name' => '', 'email' => '', 'message' => ''];

// Pre-fill for logged-in users.
if (is_logged_in()) {
    $stmt = $pdo->prepare('SELECT first_name, last_name, email FROM users WHERE id = ?');
    $stmt->execute([current_user_id()]);
    if ($me = $stmt->fetch()) {
        $old['name']  = $me['first_name'] . ' ' . $me['last_name'];
        $old['email'] = $me['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old = [
        'name'    => clean($_POST['name'] ?? ''),
        'email'   => strtolower(clean($_POST['email'] ?? '')),
        'message' => trim($_POST['message'] ?? ''),
    ];

    if (!csrf_valid())                               { $errors['form']    = 'Your session expired. Please send the message again.'; }
    if ($old['name'] === '')                         { $errors['name']    = 'Enter your name.'; }
    if (!valid_email($old['email']))                 { $errors['email']   = 'Enter a valid email address.'; }
    if (mb_strlen($old['message']) < 15)             { $errors['message'] = 'Tell us a little more (at least 15 characters).'; }
    if (mb_strlen($old['message']) > 2000)           { $errors['message'] = 'Keep the message under 2000 characters.'; }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO messages (name, email, message) VALUES (?, ?, ?)');
        $stmt->execute([$old['name'], $old['email'], $old['message']]);

        /* Optional email notification. Install PHPMailer with Composer, then
           remove the comment markers below and fill in your SMTP details.

        require __DIR__ . '/vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'you@example.com';
        $mail->Password   = 'your-app-password';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom('no-reply@example.com', 'Ledgerly');
        $mail->addAddress('support@example.com');
        $mail->addReplyTo($old['email'], $old['name']);
        $mail->Subject = 'New message through the Ledgerly contact form';
        $mail->Body    = $old['message'];
        $mail->send();
        */

        set_flash('success', 'Thank you. Your message has been received and we will reply by email.');
        header('Location: contact.php');
        exit;
    }
}

$root       = '';
$activePage = 'contact';
$pageTitle  = 'Contact';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-5">
        <h1 class="mb-3">Get in touch</h1>
        <p class="text-muted">
          Found something that does not add up, or want a category we do not offer yet?
          Send a message and we will get back to you by email.
        </p>

        <div class="panel mt-4">
          <h2 class="h6 mb-3">Common questions</h2>
          <p class="mb-2 fw-semibold">Can anyone else see my entries?</p>
          <p class="form-hint">No. Every row is stored against your user id and each query filters on it.</p>
          <p class="mb-2 fw-semibold">I forgot my password.</p>
          <p class="form-hint mb-0">Send a message from the email address on your account and we will help you reset it.</p>
        </div>
      </div>

      <div class="col-lg-7">
        <?= show_flash() ?>
        <?php if (!empty($errors['form'])): ?>
          <div class="alert alert-danger"><?= e($errors['form']) ?></div>
        <?php endif; ?>

        <div class="panel">
          <form action="contact.php" method="post" novalidate data-validate>
            <?= csrf_field() ?>

            <div class="mb-3">
              <label class="form-label" for="name">Your name</label>
              <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                     type="text" id="name" name="name" data-rule="required|name"
                     value="<?= e($old['name']) ?>" autocomplete="name" required>
              <span class="field-error" data-error-for="name"><?= e($errors['name'] ?? '') ?></span>
            </div>

            <div class="mb-3">
              <label class="form-label" for="email">Email address</label>
              <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                     type="email" id="email" name="email" data-rule="required|email"
                     value="<?= e($old['email']) ?>" autocomplete="email" required>
              <span class="field-error" data-error-for="email"><?= e($errors['email'] ?? '') ?></span>
              <small class="form-hint">We only use this to reply to you.</small>
            </div>

            <div class="mb-3">
              <label class="form-label" for="message">Message</label>
              <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                        id="message" name="message" rows="6" data-rule="required|message"
                        maxlength="2000" required><?= e($old['message']) ?></textarea>
              <span class="field-error" data-error-for="message"><?= e($errors['message'] ?? '') ?></span>
              <small class="form-hint"><span id="messageCount">0</span> of 2000 characters</small>
            </div>

            <button class="btn btn-accent" type="submit">Send message</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  var messageField = document.getElementById('message');
  var messageCount = document.getElementById('messageCount');
  function updateCount() { messageCount.textContent = messageField.value.length; }
  messageField.addEventListener('input', updateCount);
  updateCount();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
