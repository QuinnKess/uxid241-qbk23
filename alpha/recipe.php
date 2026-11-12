<?php
declare(strict_types=1);

// Recipe detail page: one recipe's full content (recipe.php?id=1).
// Written with help from Claude (AI). See README, "AI use".

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/theme.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/recipe_queries.php';

// Which recipe to show, from the link. User input: must be a whole number
// of 1 or more. Anything else gives false or null and shows "not found".
$recipe_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
  'options' => ['min_range' => 1],
]);

$recipe = null;
$ingredients = [];
$steps = [];
$tip = null;
$tools = [];
$load_failed = false;

// Load the recipe and its related rows (see includes/recipe_queries.php).
// Each query passes $recipe_id through a ? placeholder.
try {
  if (is_int($recipe_id)) {
    require __DIR__ . '/../includes/db.php';

    $recipe = find_recipe($db, $recipe_id);

    if ($recipe !== null) {
      $ingredients = recipe_ingredients($db, $recipe_id);
      $steps = recipe_steps($db, $recipe_id);
      $tip = recipe_tip($db, $recipe_id);
      $tools = recipe_tools($db, $recipe_id);
    }
  }
} catch (mysqli_sql_exception $error) {
  error_log($error->getMessage());
  http_response_code(500);
  $load_failed = true;
}

// No such recipe: tell the browser with a 404 status, not just the page text.
if (!$load_failed && $recipe === null) {
  http_response_code(404);
}

$page_title = $recipe !== null ? $recipe['title'] : 'Recipe not found';
$current_page = 'recipes';
$page_params = ['id' => is_int($recipe_id) ? $recipe_id : null];
$main_class = 'wide';
$footer_label = 'Week 5 — Recipe detail';

require __DIR__ . '/../templates/header.php';

?>

  <p class="eyebrow"><a class="back-link" href="recipes.php">&larr; All recipes</a></p>

  <?php if ($load_failed): ?>

    <p class="errors">This recipe couldn't be loaded. Please try again later.</p>

  <?php elseif ($recipe === null): ?>

    <h1>Recipe not found</h1>
    <p class="empty-state">
      That recipe doesn't exist. <a href="recipes.php">Browse all recipes</a>.
    </p>

  <?php else: ?>

    <article class="recipe-detail">

      <p class="recipe-meta">
        <a href="<?= e(page_link('recipes.php', ['category' => (int) $recipe['category_id']])) ?>"><?= e($recipe['category']) ?></a>
        &middot; Serves <?= e((string) $recipe['servings']) ?>
      </p>
      <h1 class="recipe-title"><?= e($recipe['title']) ?></h1>
      <p class="recipe-subtitle recipe-subtitle-large"><?= e($recipe['subtitle']) ?></p>

      <?php if ($recipe['main_image'] !== ''): ?>
        <img class="recipe-hero" src="<?= e($recipe['main_image']) ?>" alt="<?= e($recipe['title']) ?>">
      <?php endif; ?>

      <p class="recipe-description"><?= nl2br(e($recipe['description'])) ?></p>

      <div class="recipe-columns">

        <section class="recipe-ingredients" aria-labelledby="ingredients-heading">
          <h2 id="ingredients-heading">Ingredients</h2>
          <?php if ($ingredients === []): ?>
            <p class="empty-state">No ingredients added yet.</p>
          <?php else: ?>
            <ul>
              <?php foreach ($ingredients as $ingredient): ?>
                <li><?= e($ingredient['full_text']) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <?php if ($recipe['ingredients_image'] !== ''): ?>
            <img
              src="<?= e($recipe['ingredients_image']) ?>"
              alt="All the ingredients for <?= e($recipe['title']) ?>"
              loading="lazy"
            >
          <?php endif; ?>
        </section>

        <section class="recipe-steps" aria-labelledby="steps-heading">
          <h2 id="steps-heading">Steps</h2>
          <?php if ($steps === []): ?>
            <p class="empty-state">No steps added yet.</p>
          <?php endif; ?>
          <ol>
            <?php foreach ($steps as $step): ?>
              <li>
                <?php if ($step['image'] !== null): ?>
                  <img
                    src="<?= e($step['image']) ?>"
                    <?php if ($step['image_retina'] !== null): ?>
                      srcset="<?= e($step['image']) ?> 1x, <?= e($step['image_retina']) ?> 2x"
                    <?php endif; ?>
                    alt="Step <?= e((string) $step['step_number']) ?>: <?= e($step['step_title']) ?>"
                    loading="lazy"
                  >
                <?php endif; ?>
                <h3>
                  <span class="step-number"><?= e((string) $step['step_number']) ?></span>
                  <?= e($step['step_title']) ?>
                </h3>
                <p><?= nl2br(e($step['instructions'])) ?></p>
              </li>
            <?php endforeach; ?>
          </ol>
        </section>

      </div>

      <?php if ($tip !== null): ?>
        <section class="recipe-extra" aria-labelledby="tip-heading">
          <img src="<?= e($tip['image']) ?>" alt="" loading="lazy">
          <div>
            <h2 id="tip-heading">
              <?= $tip['tip_type'] === 'how_to' ? 'How To: ' : '' ?><?= e($tip['tip_title']) ?>
            </h2>
            <p><?= nl2br(e($tip['tip_text'])) ?></p>
          </div>
        </section>
      <?php endif; ?>

      <?php foreach ($tools as $tool): ?>
        <section class="recipe-extra">
          <img src="<?= e($tool['image']) ?>" alt="<?= e($tool['name']) ?>" loading="lazy">
          <div>
            <p class="recipe-meta">Kitchen tool</p>
            <h2><?= e($tool['name']) ?></h2>
            <?php if ($tool['brand'] !== null): ?>
              <p class="tool-brand"><?= e($tool['brand']) ?></p>
            <?php endif; ?>
            <p><?= e($tool['description']) ?></p>
          </div>
        </section>
      <?php endforeach; ?>

    </article>

  <?php endif; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>
