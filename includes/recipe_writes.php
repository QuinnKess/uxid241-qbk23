<?php
declare(strict_types=1);

// Admin recipe writes: read and validate the recipe form, then create,
// update or delete a recipe.
// Written with help from Claude (AI). See README, "AI use".
//
// Every write uses a prepared statement: each value from the form goes
// through a ? placeholder and mysqli_execute_query, never into the SQL string.

// The recipe form's fields, as submitted text (used to refill the form).
function read_recipe_form(): array {
  return [
    'title' => post_value('title'),
    'subtitle' => post_value('subtitle'),
    'category_id' => post_value('category_id'),
    'servings' => post_value('servings'),
    'description' => post_value('description'),
  ];
}

// Check the form on the server. Returns error messages keyed by field name;
// an empty array means the form is valid. $category_ids are the real ids.
function validate_recipe_form(array $form, array $category_ids): array {
  $errors = [];

  if ($form['title'] === '') {
    $errors['title'] = 'Enter a title.';
  } elseif (mb_strlen($form['title']) > 150) {
    $errors['title'] = 'Keep the title to 150 characters or fewer.';
  }

  if (mb_strlen($form['subtitle']) > 150) {
    $errors['subtitle'] = 'Keep the subtitle to 150 characters or fewer.';
  }

  $category_id = filter_var($form['category_id'], FILTER_VALIDATE_INT);
  if (!in_array($category_id, $category_ids, true)) {
    $errors['category_id'] = 'Choose a category.';
  }

  $servings = filter_var($form['servings'], FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 20],
  ]);
  if ($servings === false) {
    $errors['servings'] = 'Servings must be a whole number from 1 to 20.';
  }

  if ($form['description'] === '') {
    $errors['description'] = 'Enter a description.';
  } elseif (mb_strlen($form['description']) > 5000) {
    $errors['description'] = 'Keep the description to 5,000 characters or fewer.';
  }

  return $errors;
}

// Turn a title into a URL-friendly name: "Pork & Rice!" becomes "pork-rice".
function make_slug(string $title): string {
  $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($title));
  $slug = trim(substr($slug, 0, 90), '-');

  return $slug === '' ? 'recipe' : $slug;
}

// A slug no other recipe uses yet: "pork-rice", then "pork-rice-2", and so on.
function unique_recipe_slug(mysqli $db, string $title): string {
  $base = make_slug($title);
  $slug = $base;
  $number = 2;

  while (true) {
    $result = mysqli_execute_query($db, 'SELECT 1 FROM recipes WHERE slug = ?', [$slug]);
    if (mysqli_fetch_row($result) === null) {
      return $slug;
    }
    $slug = $base . '-' . $number;
    $number++;
  }
}

// Add a recipe from a valid form and return its new id.
// New recipes start with no photos, ingredients or steps; the fixed
// values (0 and '') are written into the SQL because they never come
// from the user.
function create_recipe(mysqli $db, array $form): int {
  $sql = "INSERT INTO recipes
            (slug, title, subtitle, category_id, servings, description,
             ingredient_count, step_count, main_image, ingredients_image,
             image_folder, source_pdf)
          VALUES (?, ?, ?, ?, ?, ?, 0, 0, '', '', '', '')";

  mysqli_execute_query($db, $sql, [
    unique_recipe_slug($db, $form['title']),
    $form['title'],
    $form['subtitle'],
    (int) $form['category_id'],
    (int) $form['servings'],
    $form['description'],
  ]);

  return (int) mysqli_insert_id($db);
}

// Save a valid form over an existing recipe. WHERE recipe_id = ? limits the
// change to that one recipe. The slug stays the same, since image folders use it.
function update_recipe(mysqli $db, int $recipe_id, array $form): void {
  $sql = 'UPDATE recipes
          SET title = ?, subtitle = ?, category_id = ?, servings = ?, description = ?
          WHERE recipe_id = ?';

  mysqli_execute_query($db, $sql, [
    $form['title'],
    $form['subtitle'],
    (int) $form['category_id'],
    (int) $form['servings'],
    $form['description'],
    $recipe_id,
  ]);
}

// Delete one recipe. WHERE recipe_id = ? means it can never delete more than
// one row. The foreign keys' ON DELETE CASCADE also removes that recipe's
// ingredients, steps, tip, tool links and image rows.
// Returns true if a recipe was actually deleted.
function delete_recipe(mysqli $db, int $recipe_id): bool {
  mysqli_execute_query($db, 'DELETE FROM recipes WHERE recipe_id = ?', [$recipe_id]);

  return mysqli_affected_rows($db) === 1;
}
