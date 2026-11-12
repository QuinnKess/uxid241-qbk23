<?php
declare(strict_types=1);

// Admin: delete a recipe (delete.php?id=1).
// Opening the link only shows a confirmation page; the recipe is deleted
// only when that page's form is POSTed with a valid CSRF token.
// Written with help from Claude (AI). See README, "AI use".

require __DIR__ . '/../../includes/helpers.php';
require __DIR__ . '/../../includes/theme.php';
require __DIR__ . '/../../includes/auth.php';

// Guard: visitors who aren't logged in go to the login page and stop here.
require_login('../login.php');

require __DIR__ . '/../../includes/recipe_queries.php';
require __DIR__ . '/../../includes/recipe_writes.php';

// Which recipe, from the link. Must be a whole number of 1 or more.
$recipe_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
  'options' => ['min_range' => 1],
]);

$recipe = null;
$load_failed = false;

try {
  require __DIR__ . '/../../includes/db.php';

  if (is_int($recipe_id)) {
    $recipe = find_recipe($db, $recipe_id);
  }

  if ($recipe !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid(post_value('csrf_token'))) {
      http_response_code(403);
      exit('Invalid form token. Go back, reload the page and try again.');
    }

    delete_recipe($db, $recipe_id);
    set_flash('Deleted "' . $recipe['title'] . '".');

    header('Location: index.php');
    exit;
  }
} catch (mysqli_sql_exception $error) {
  error_log($error->getMessage());
  http_response_code(500);
  $load_failed = true;
}

if (!$load_failed && $recipe === null) {
  http_response_code(404);
}

$page_title = $recipe !== null ? 'Delete ' . $recipe['title'] : 'Recipe not found';
$current_page = 'admin';
$base_path = '../';
$page_params = ['id' => is_int($recipe_id) ? $recipe_id : null];
$footer_label = 'Week 7 — Beta';

require __DIR__ . '/../../templates/header.php';

?>

  <p class="eyebrow"><a class="back-link" href="index.php">&larr; Recipe admin</a></p>

  <?php if ($load_failed): ?>
    <h1>Delete recipe</h1>
    <p class="errors">The recipe couldn't be deleted. Please try again later.</p>
  <?php elseif ($recipe === null): ?>
    <h1>Recipe not found</h1>
    <p class="empty-state">That recipe doesn't exist. <a href="index.php">Back to recipe admin</a>.</p>
  <?php else: ?>
    <h1>Delete recipe</h1>
    <p class="confirm-text">
      Delete <strong><?= e($recipe['title']) ?></strong>?
      This also removes its ingredients, steps, tip and photo list, and can't be undone.
    </p>

    <form method="POST" class="form-actions">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <button type="submit" class="button-danger">Delete recipe</button>
      <a class="back-link" href="index.php">Cancel</a>
    </form>
  <?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
