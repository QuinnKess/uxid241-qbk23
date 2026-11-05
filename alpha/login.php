<?php
declare(strict_types=1);

// Admin login: look the account up by email, check the password with
// password_verify(), then start a fresh logged-in session.
// Written with help from Claude (AI). See README, "AI use".

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/theme.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/user_queries.php';

// Already logged in: go straight to the admin area.
if (current_user_id() !== null) {
  header('Location: admin/index.php');
  exit;
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // The form must carry this session's CSRF token, which proves it came
  // from our own login page and not a form on another site.
  if (!csrf_is_valid(post_value('csrf_token'))) {
    http_response_code(403);
    exit('Invalid form token. Go back, reload the page and try again.');
  }

  $email = post_value('email');

  // Passwords are used exactly as typed (not trimmed), and must be text.
  $raw_password = $_POST['password'] ?? '';
  $password = is_string($raw_password) ? $raw_password : '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $error = 'Enter your email and password.';
  } else {
    try {
      require __DIR__ . '/../includes/db.php';

      // Look up by email only (prepared statement). The password never goes
      // into SQL: the database returns the stored hash, and PHP checks it.
      $user = find_user_by_email($db, $email);

      // Both must be true: the account exists, and password_verify() confirms
      // the typed password matches the stored password_hash() result.
      if ($user !== null && password_verify($password, $user['password_hash'])) {
        log_in_user((int) $user['user_id']);
        header('Location: admin/index.php');
        exit;
      }

      // One vague message for both "no such email" and "wrong password",
      // so the form doesn't reveal which emails have accounts.
      $error = 'Invalid email or password.';
    } catch (mysqli_sql_exception $db_error) {
      error_log($db_error->getMessage());
      http_response_code(500);
      $error = 'Logging in isn\'t available right now. Please try again later.';
    }
  }
}

$logged_out = isset($_GET['logged_out']);

$page_title = 'Log in';
$current_page = 'login';
$footer_label = 'Week 6 — Sessions and authentication';

require __DIR__ . '/../templates/header.php';

?>

  <p class="eyebrow">[ Admin ]</p>
  <h1>Log <em>in</em></h1>

  <?php if ($error !== ''): ?>
    <p class="errors"><?= e($error) ?></p>
  <?php elseif ($logged_out): ?>
    <p class="success">You're logged out.</p>
  <?php endif; ?>

  <form method="POST" action="login.php">

    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <label for="email">Email</label>
    <input
      type="email"
      id="email"
      name="email"
      value="<?= e($email) ?>"
      autocomplete="username"
      required
    >

    <label for="password">Password</label>
    <input
      type="password"
      id="password"
      name="password"
      autocomplete="current-password"
      required
    >

    <input type="submit" value="Log in">

  </form>

<?php require __DIR__ . '/../templates/footer.php'; ?>
