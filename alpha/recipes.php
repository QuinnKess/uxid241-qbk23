<?php
declare(strict_types=1);

// Recipe library: browse, search and filter by category.
// Written with help from Claude (AI). See README, "AI use".

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/theme.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/recipe_queries.php';

// Search words from the search box (?q=garlic). User input: must be text,
// trimmed and capped at 100 characters. Anything else counts as no search.
$raw_search = $_GET['q'] ?? '';
$search = is_string($raw_search) ? mb_substr(trim($raw_search), 0, 100) : '';

// Category from a filter link (?category=2). User input: must be a whole
// number of 1 or more. Anything else (text, negative, missing) shows all.
$requested_id = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT, [
  'options' => ['min_range' => 1],
]);

$recipes = [];
$categories = [];
$selected_category = null;
$selected_id = null;
$load_failed = false;

// Load the data (see includes/recipe_queries.php). If the database fails,
// log the real error privately and show a generic message, so table names
// never leak onto the page.
try {
  require __DIR__ . '/../includes/db.php';

  $categories = categories_with_counts($db);

  // Only filter if the requested id is a category that really exists.
  foreach ($categories as $category) {
    if ((int) $category['category_id'] === $requested_id) {
      $selected_category = $category;
    }
  }

  $selected_id = $selected_category !== null ? (int) $selected_category['category_id'] : null;
  $recipes = find_recipes($db, $search, $selected_id);
} catch (mysqli_sql_exception $error) {
  error_log($error->getMessage());
  http_response_code(500);
  $load_failed = true;
}

$total_recipes = array_sum(array_column($categories, 'recipe_count'));

// Sentence above the grid, for example: 3 recipes matching "garlic" in Chicken.
$result_count = count($recipes);
$summary = $result_count . ' ' . ($result_count === 1 ? 'recipe' : 'recipes');
if ($search !== '') {
  $summary .= ' matching "' . $search . '"';
}
if ($selected_category !== null) {
  $summary .= ' in ' . $selected_category['name'];
}

$page_title = 'Recipes';
$current_page = 'recipes';
$page_params = ['q' => $search, 'category' => $selected_id];
$main_class = 'wide';
$footer_label = 'Week 5 — Recipe library';

require __DIR__ . '/../templates/header.php';

?>

  <p class="eyebrow">[ Recipe library ]</p>
  <h1>Recipes from the Database</h1>

  <form class="search-form" action="recipes.php" method="get" role="search">
    <label for="q" class="visually-hidden">Search recipes</label>
    <input
      type="search"
      id="q"
      name="q"
      value="<?= e($search) ?>"
      placeholder="Search by name or ingredient"
      maxlength="100"
    >
    <?php if ($selected_id !== null): ?>
      <input type="hidden" name="category" value="<?= e((string) $selected_id) ?>">
    <?php endif; ?>
    <button type="submit">Search</button>
    <?php if ($search !== ''): ?>
      <a class="clear-search" href="<?= e(page_link('recipes.php', ['category' => $selected_id])) ?>">Clear</a>
    <?php endif; ?>
  </form>

  <nav class="category-links" aria-label="Filter recipes by category">
    <a href="<?= e(page_link('recipes.php', ['q' => $search])) ?>"<?= $selected_id === null ? ' aria-current="page"' : '' ?>>
      All <span class="link-count"><?= e((string) $total_recipes) ?></span>
    </a>
    <?php foreach ($categories as $category): ?>
      <a
        href="<?= e(page_link('recipes.php', ['q' => $search, 'category' => (int) $category['category_id']])) ?>"
        <?= (int) $category['category_id'] === $selected_id ? 'aria-current="page"' : '' ?>
      >
        <?= e($category['name']) ?> <span class="link-count"><?= e((string) $category['recipe_count']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($load_failed): ?>
    <p class="errors">The recipes couldn't be loaded. Please try again later.</p>
  <?php else: ?>
    <p class="count"><?= e($summary) ?></p>
  <?php endif; ?>

  <?php if (!$load_failed && $recipes === []): ?>
    <p class="empty-state">
      No recipes found. Try a different word or
      <a href="recipes.php">see all recipes</a>.
    </p>
  <?php endif; ?>

  <ul class="recipe-grid">
    <?php foreach ($recipes as $recipe): ?>
      <li class="recipe-card">
        <a class="recipe-link" href="<?= e(page_link('recipe.php', ['id' => (int) $recipe['recipe_id']])) ?>">
          <img
            src="<?= e($recipe['main_image']) ?>"
            alt="<?= e($recipe['title']) ?>"
            loading="lazy"
          >
          <p class="recipe-meta">
            <?= e($recipe['category']) ?> &middot; Serves <?= e((string) $recipe['servings']) ?>
          </p>
          <h2><?= e($recipe['title']) ?></h2>
          <p class="recipe-subtitle"><?= e($recipe['subtitle']) ?></p>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

<?php require __DIR__ . '/../templates/footer.php'; ?>
