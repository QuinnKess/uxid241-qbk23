<?php
declare(strict_types=1);

// Admin: edit a recipe (edit.php?id=1).
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
$form = [];
$errors = [];
$categories = [];
$load_failed = false;

try {
  require __DIR__ . '/../../includes/db.php';

  if (is_int($recipe_id)) {
    $recipe = find_recipe($db, $recipe_id);
  }

  if ($recipe !== null) {
    $categories = categories_with_counts($db);
    $category_ids = array_map('intval', array_column($categories, 'category_id'));

    // Start with the saved recipe; on a POST, what was typed replaces it.
    $form = [
      'title' => $recipe['title'],
      'subtitle' => $recipe['subtitle'],
      'category_id' => (string) $recipe['category_id'],
      'servings' => (string) $recipe['servings'],
      'description' => $recipe['description'],
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (!csrf_is_valid(post_value('csrf_token'))) {
        http_response_code(403);
        exit('Invalid form token. Go back, reload the page and try again.');
      }

      $form = read_recipe_form();
      $errors = validate_recipe_form($form, $category_ids);

      // Only write when every field passed validation.
      if ($errors === []) {
        update_recipe($db, $recipe_id, $form);
        set_flash('Saved "' . $form['title'] . '".');

        header('Location: index.php');
        exit;
      }

      http_response_code(422);
    }
  }
} catch (mysqli_sql_exception $error) {
  error_log($error->getMessage());
  http_response_code(500);
  $load_failed = true;
}

if (!$load_failed && $recipe === null) {
  http_response_code(404);
}

$page_title = $recipe !== null ? 'Edit ' . $recipe['title'] : 'Recipe not found';
$current_page = 'admin';
$base_path = '../';
$page_params = ['id' => is_int($recipe_id) ? $recipe_id : null];
$footer_label = 'Week 7 — Beta';
$submit_label = 'Save changes';
$cancel_url = 'index.php';

require __DIR__ . '/../../templates/header.php';

?>

  <p class="eyebrow"><a class="back-link" href="index.php">&larr; Recipe admin</a></p>

  <?php if ($load_failed): ?>
    <h1>Edit recipe</h1>
    <p class="errors">The recipe couldn't be loaded or saved. Please try again later.</p>
  <?php elseif ($recipe === null): ?>
    <h1>Recipe not found</h1>
    <p class="empty-state">That recipe doesn't exist. <a href="index.php">Back to recipe admin</a>.</p>
  <?php else: ?>
    <h1>Edit recipe</h1>
    <?php require __DIR__ . '/../../templates/recipe_form.php'; ?>
  <?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
