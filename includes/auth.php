<?php
declare(strict_types=1);

// Sessions, login state, the admin guard and CSRF tokens.
// Written with help from Claude (AI). See README, "AI use".
//
// Including this file starts the session, so it must be required before
// any output (no echo or HTML before it), because the session sends a cookie.

// Reject session ids the server didn't create itself (helps stop fixation).
ini_set('session.use_strict_mode', '1');

// Harden the session cookie before session_start() sends it.
//   httponly: JavaScript can't read the cookie.
//   secure:   only sent over HTTPS. Turned on automatically when the site
//             runs on HTTPS; plain-HTTP local MAMP/XAMPP leaves it off.
//   samesite: not attached to most requests coming from other sites.
$is_https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_set_cookie_params([
  'path' => '/',
  'httponly' => true,
  'secure' => $is_https,
  'samesite' => 'Lax',
]);

session_start();

// The logged-in user's id, or null for a visitor who isn't logged in.
// Only the id is kept in the session; everything else comes from the database.
function current_user_id(): ?int {
  $user_id = $_SESSION['user_id'] ?? null;
  return is_int($user_id) ? $user_id : null;
}

// Mark the user as logged in. Called only after password_verify() succeeds.
function log_in_user(int $user_id): void {
  // Swap to a brand-new session id at the moment the visitor becomes logged
  // in. true deletes the old session, so an id an attacker planted is useless.
  session_regenerate_id(true);
  $_SESSION['user_id'] = $user_id;
}

// End the session completely: data, server-side session and cookie.
function log_out_user(): void {
  $_SESSION = [];

  $params = session_get_cookie_params();
  setcookie(session_name(), '', [
    'expires' => time() - 3600,
    'path' => $params['path'],
    'httponly' => $params['httponly'],
    'secure' => $params['secure'],
    'samesite' => $params['samesite'],
  ]);

  session_destroy();
}

// Guard for pages that need a login. Put it at the top of the page.
// header() only queues the redirect, so exit stops the page from rendering.
function require_login(string $login_url): void {
  if (current_user_id() === null) {
    header('Location: ' . $login_url);
    exit;
  }
}

// The session's CSRF token, made once per session from random bytes.
// Every form that changes data includes it as a hidden field.
function csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }

  return $_SESSION['csrf_token'];
}

// Check a submitted token against the session's. hash_equals() compares
// in constant time, so the check doesn't leak the token through timing.
function csrf_is_valid(string $sent_token): bool {
  $known_token = $_SESSION['csrf_token'] ?? '';
  return $known_token !== '' && hash_equals($known_token, $sent_token);
}
