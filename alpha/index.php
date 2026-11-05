<?php
declare(strict_types=1);

// Recipe submission form with server-side validation (week 2).
// Reviewed, fixed and styled with help from Claude (AI). See README, "AI use".

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/theme.php';
require __DIR__ . '/../includes/auth.php';

$name = '';
$email = '';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = post_value('name');
  $email = post_value('email');

  if ($name === '') {
    $errors[] = 'Recipe name is required.';
  } elseif (mb_strlen($name) > 100) {
    $errors[] = 'Recipe name must be 100 characters or fewer.';
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
  }

  if (empty($errors)) {
    $success = true;
  }
}

$page_title = 'Recipe Submission';
$current_page = 'form';
$footer_label = 'Assignment 2 — Forms and User Input';

require __DIR__ . '/../templates/header.php';

?>

  <p class="eyebrow">[ Assignment 2 ]</p>
  <h1>Forms and User Input</h1>

  <?php if (!empty($errors)): ?>
    <ul class="errors">
      <?php foreach ($errors as $error): ?>
        <li><?= e($error) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if ($success): ?>
    <p class="success">Thanks, <?= e($name) ?> was submitted.</p>
  <?php endif; ?>

  <form method="POST">

    <label for="name">Recipe name</label>
    <input
      type="text"
      id="name"
      name="name"
      value="<?= e($name) ?>"
    >

    <label for="email">Email</label>
    <input
      type="email"
      id="email"
      name="email"
      value="<?= e($email) ?>"
    >

    <input type="submit" value="Submit">

  </form>

<?php require __DIR__ . '/../templates/footer.php'; ?>
