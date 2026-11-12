<?php
declare(strict_types=1);

// Admin: add a recipe.
// Written with help from Claude (AI). See README, "AI use".

require __DIR__ . '/../../includes/helpers.php';
require __DIR__ . '/../../includes/theme.php';
require __DIR__ . '/../../includes/auth.php';

// Guard: visitors who aren't logged in go to the login page and stop here.
require_login('../login.php');

require __DIR__ . '/../../includes/recipe_queries.php';
require __DIR__ . '/../../includes/recipe_writes.php';

$form = [
  'title' => '',
  'subtitle' => '',
  'category_id' => '',
  'servings' => '',
  'description' => '',
];
$errors = [];
$categories = [];
$load_failed = false;

try {
  require __DIR__ . '/../../includes/db.php';

  $categories = categories_with_counts($db);
  $category_ids = array_map('intval', array_column($categories, 'category_id'));

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid(post_value('csrf_token'))) {
      http_response_code(403);
      exit('Invalid form token. Go back, reload the page and try again.');
    }

    $form = read_recipe_form();
    $errors = validate_recipe_form($form, $category_ids);

    // Only write when every field passed validation.
    if ($errors === []) {
      create_recipe($db, $form);
      set_flash('Added "' . $form['title'] . '".');

      // Redirect after a successful POST, so refreshing doesn't add it twice.
      header('Location: index.php');
      exit;
    }

    http_response_code(422);
  }
} catch (mysqli_sql_exception $error) {
  error_log($error->getMessage());
  http_response_code(500);
  $load_failed = true;
}

$page_title = 'Add a recipe';
$current_page = 'admin';
$base_path = '../';
$footer_label = 'Week 7 — Beta';
$submit_label = 'Add recipe';
$cancel_url = 'index.php';

require __DIR__ . '/../../templates/header.php';

?>

  <p class="eyebrow"><a class="back-link" href="index.php">&larr; Recipe admin</a></p>
  <h1>Add a recipe</h1>

  <?php if ($load_failed): ?>
    <p class="errors">The recipe couldn't be saved. Please try again later.</p>
  <?php else: ?>
    <?php require __DIR__ . '/../../templates/recipe_form.php'; ?>
  <?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
