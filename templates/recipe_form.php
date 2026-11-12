<?php
declare(strict_types=1);

// The add / edit recipe form, shared by admin/create.php and admin/edit.php.
// Written with help from Claude (AI). See README, "AI use".
//
// The page sets these before including this file:
//   $form          field values to show (what was typed, or the saved recipe)
//   $errors        error messages keyed by field name ([] when none)
//   $categories    rows with category_id and name, for the dropdown
//   $submit_label  text on the save button
//   $cancel_url    where Cancel goes

// Error message under a field, if that field has one.
$field_error = function (string $field) use ($errors): string {
  if (!isset($errors[$field])) {
    return '';
  }

  return '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</p>';
};

// Attributes that link a field to its error message for screen readers.
$error_attributes = function (string $field) use ($errors): string {
  return isset($errors[$field])
    ? ' aria-invalid="true" aria-describedby="' . e($field) . '-error"'
    : '';
};

?>
  <?php if ($errors !== []): ?>
    <p class="errors">Please fix the highlighted fields.</p>
  <?php endif; ?>

  <form method="POST" class="recipe-form" novalidate>

    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <label for="title">Title</label>
    <input
      type="text"
      id="title"
      name="title"
      value="<?= e($form['title']) ?>"
      maxlength="150"
      required<?= $error_attributes('title') ?>
    >
    <?= $field_error('title') ?>

    <label for="subtitle">Subtitle <span class="optional">(optional)</span></label>
    <input
      type="text"
      id="subtitle"
      name="subtitle"
      value="<?= e($form['subtitle']) ?>"
      maxlength="150"
      placeholder="with Roasted Vegetables"<?= $error_attributes('subtitle') ?>
    >
    <?= $field_error('subtitle') ?>

    <label for="category_id">Category</label>
    <select id="category_id" name="category_id" required<?= $error_attributes('category_id') ?>>
      <option value="">Choose a category</option>
      <?php foreach ($categories as $category): ?>
        <option
          value="<?= e((string) $category['category_id']) ?>"
          <?= (string) $category['category_id'] === $form['category_id'] ? 'selected' : '' ?>
        ><?= e($category['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?= $field_error('category_id') ?>

    <label for="servings">Servings</label>
    <input
      type="number"
      id="servings"
      name="servings"
      value="<?= e($form['servings']) ?>"
      min="1"
      max="20"
      step="1"
      required<?= $error_attributes('servings') ?>
    >
    <?= $field_error('servings') ?>

    <label for="description">Description</label>
    <textarea
      id="description"
      name="description"
      rows="6"
      maxlength="5000"
      required<?= $error_attributes('description') ?>
    ><?= e($form['description']) ?></textarea>
    <?= $field_error('description') ?>

    <div class="form-actions">
      <input type="submit" value="<?= e($submit_label) ?>">
      <a class="back-link" href="<?= e($cancel_url) ?>">Cancel</a>
    </div>

  </form>
