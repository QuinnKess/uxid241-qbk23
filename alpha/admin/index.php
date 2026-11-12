<?php
declare(strict_types=1);

// Admin home: every recipe with links to edit or delete it, and a link to
// add a new one. Only for logged-in users: the guard below sends anyone
// else to the login page before any of this page runs.
// Written with help from Claude (AI). See README, "AI use".

require __DIR__ . '/../../includes/helpers.php';
require __DIR__ . '/../../includes/theme.php';
require __DIR__ . '/../../includes/auth.php';

require_login('../login.php');

require __DIR__ . '/../../includes/user_queries.php';
require __DIR__ . '/../../includes/recipe_queries.php';

$user = null;
$recipes = [];
$load_failed = false;
$flash = take_flash();

// Only the user id is in the session, so look up the email from the database.
try {
  require __DIR__ . '/../../includes/db.php';

  $user = find_user_by_id($db, (int) current_user_id());
  $recipes = find_recipes($db);
} catch (mysqli_sql_exception $error) {
  error_log($error->getMessage());
  http_response_code(500);
  $load_failed = true;
}

$page_title = 'Admin';
$current_page = 'admin';
$base_path = '../';
$main_class = 'wide';
$footer_label = 'Week 7 — Beta';

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

    <?php if ($flash !== null): ?>
      <p class="success"><?= e($flash) ?></p>
    <?php endif; ?>

    <div class="admin-toolbar">
      <a class="button" href="create.php">Add a recipe</a>
      <span class="count"><?= e((string) count($recipes)) ?> recipes</span>
    </div>

    <table class="admin-table">
      <thead>
        <tr>
          <th scope="col">Recipe</th>
          <th scope="col">Category</th>
          <th scope="col">Serves</th>
          <th scope="col"><span class="visually-hidden">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recipes as $recipe): ?>
          <tr>
            <td>
              <a href="<?= e(page_link('../recipe.php', ['id' => (int) $recipe['recipe_id']])) ?>"><?= e($recipe['title']) ?></a>
            </td>
            <td><?= e($recipe['category']) ?></td>
            <td><?= e((string) $recipe['servings']) ?></td>
            <td class="admin-actions">
              <a href="<?= e(page_link('edit.php', ['id' => (int) $recipe['recipe_id']])) ?>">Edit</a>
              <a class="danger-link" href="<?= e(page_link('delete.php', ['id' => (int) $recipe['recipe_id']])) ?>">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
