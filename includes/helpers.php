<?php
declare(strict_types=1);

// Small helpers shared by every page.
// Written with help from Claude (AI). See README, "AI use".

// Escape a value before printing it into HTML.
function e(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Build a link with a query string, leaving out empty values.
// page_link('recipes.php', ['category' => 2, 'q' => '']) gives 'recipes.php?category=2'.
// The result still has to go through e() when printed into HTML.
function page_link(string $page, array $params = []): string {
  $params = array_filter($params, fn($value) => $value !== null && $value !== '');
  $query = http_build_query($params);

  return $query === '' ? ($page === '' ? '?' : $page) : $page . '?' . $query;
}

// Read a POST field as trimmed text. Anything that is not a string
// (for example name[]=x, which sends an array) counts as empty.
function post_value(string $key): string {
  $value = $_POST[$key] ?? '';
  return is_string($value) ? trim($value) : '';
}
