</main>

<footer class="site-footer">
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-5">
        <p class="footer-brand"><span class="brand-mark" aria-hidden="true"></span>Ledgerly</p>
        <p class="footer-note">A private budget book. Every figure you enter stays tied to your own account and is never shown to another user.</p>
      </div>
      <div class="col-6 col-lg-3">
        <p class="footer-heading">Pages</p>
        <ul class="footer-links">
          <li><a href="<?= $root ?>index.php">Home</a></li>
          <li><a href="<?= $root ?>dashboard.php">Dashboard</a></li>
          <li><a href="<?= $root ?>transactions.php">Transactions</a></li>
          <li><a href="<?= $root ?>contact.php">Contact</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-4">
        <p class="footer-heading">Account</p>
        <ul class="footer-links">
          <li><a href="<?= $root ?>auth/register.php">Create account</a></li>
          <li><a href="<?= $root ?>auth/login.php">Log in</a></li>
          <li><a href="<?= $root ?>profile.php">Profile</a></li>
        </ul>
      </div>
    </div>
    <p class="footer-fine">Built for the Web Application Development coursework &middot; <?= date('Y') ?></p>
  </div>
</footer>

<button class="to-top" id="toTop" type="button" aria-label="Back to top">Top</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $root ?>js/main.js"></script>
<?php if (!empty($pageScripts)): foreach ($pageScripts as $script): ?>
<script src="<?= $root . $script ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
