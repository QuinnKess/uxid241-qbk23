<?php
declare(strict_types=1);

// Admin home. Only for logged-in users: the guard below sends anyone else
// to the login page before any of this page runs.
// Written with help from Claude (AI). See README, "AI use".

require __DIR__ . '/../../includes/helpers.php';
require __DIR__ . '/../../includes/theme.php';
require __DIR__ . '/../../includes/auth.php';

require_login('../login.php');

require __DIR__ . '/../../includes/user_queries.php';
require __DIR__ . '/../../includes/recipe_queries.php';

$user = null;
$recipe_count = 0;
$load_failed = false;

// Only the user id is in the session, so look up the email from the database.
try {
  require __DIR__ . '/../../includes/db.php';

  $user = find_user_by_id($db, (int) current_user_id());
  $recipe_count = count(find_recipes($db));
} catch (mysqli_sql_exception $error) {
  error_log($error->getMessage());
  http_response_code(500);
  $load_failed = true;
}

$page_title = 'Admin';
$current_page = 'admin';
$base_path = '../';
$footer_label = 'Week 6 — Sessions and authentication';

require __DIR__ . '/../../templates/header.php';

?>

  <p class="eyebrow">[ Admin ]</p>
  <h1>Recipe admin</h1>

  <?php if ($load_failed): ?>
    <p class="errors">The admin area couldn't be loaded. Please try again later.</p>
  <?php else: ?>
    <?php if ($user !== null): ?>
      <p class="count">Signed in as <?= e($user['email']) ?></p>
    <?php endif; ?>

    <p>
      The library has <?= e((string) $recipe_count) ?> recipes.
      Adding, editing and deleting recipes will live here next.
    </p>

    <p><a class="back-link" href="../recipes.php">View the recipe library</a></p>
  <?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
