<?php
declare(strict_types=1);

// Page top: <head>, site header and navigation. Opens <main>.
// Written with help from Claude (AI). See README, "AI use".
//
// Each page sets these before including this file:
//   $page_title   text for the browser tab
//   $current_page 'form', 'recipes', 'login' or 'admin', to highlight the nav link
//   $page_params  the page's own query values, kept when switching theme
//   $main_class   'wide' for the grid pages, '' for narrow pages
//   $base_path    '' for pages in alpha/, '../' for pages in alpha/admin/
// $theme and $other_theme come from includes/theme.php;
// current_user_id() comes from includes/auth.php.

$page_params = $page_params ?? [];
$main_class = $main_class ?? '';
$base_path = $base_path ?? '';
$is_logged_in = current_user_id() !== null;

// Add the stylesheet's last-saved time so browsers load a fresh copy after edits.
$css_file = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $base_path . 'style.css';
$css_version = (string) filemtime($css_file);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= e($page_title) ?> | IDM 232</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;700&family=Instrument+Serif:ital@1&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= e($base_path) ?>style.css?v=<?= e($css_version) ?>">
</head>

<body class="theme-<?= e($theme) ?>">

<header class="site-header">
  <span>IDM 232 &middot; Quinn Kessler</span>
  <nav class="site-nav">
    <a href="<?= e($base_path) ?>index.php"<?= $current_page === 'form' ? ' aria-current="page"' : '' ?>>Form</a>
    <a href="<?= e($base_path) ?>recipes.php"<?= $current_page === 'recipes' ? ' aria-current="page"' : '' ?>>Recipes</a>
    <?php if ($is_logged_in): ?>
      <a href="<?= e($base_path) ?>admin/index.php"<?= $current_page === 'admin' ? ' aria-current="page"' : '' ?>>Admin</a>
      <a href="<?= e($base_path) ?>logout.php">Log out</a>
    <?php else: ?>
      <a href="<?= e($base_path) ?>login.php"<?= $current_page === 'login' ? ' aria-current="page"' : '' ?>>Log in</a>
    <?php endif; ?>
    <a href="<?= e(page_link('', $page_params + ['theme' => $other_theme])) ?>"><?= $theme === 'dark' ? 'Light mode' : 'Dark mode' ?></a>
  </nav>
</header>

<main<?= $main_class !== '' ? ' class="' . e($main_class) . '"' : '' ?>>
