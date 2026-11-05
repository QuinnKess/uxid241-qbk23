<?php
declare(strict_types=1);

// Database functions for user accounts.
// Written with help from Claude (AI). See README, "AI use".
//
// The password is never part of a query: the database only finds the
// account by email, and PHP checks the password with password_verify().

// The account with this email (its id and stored hash), or null if none.
function find_user_by_email(mysqli $db, string $email): ?array {
  $sql = 'SELECT user_id, password_hash
          FROM users
          WHERE email = ?';

  $result = mysqli_execute_query($db, $sql, [$email]);
  return mysqli_fetch_assoc($result);
}

// The account with this id (without the hash), or null if none.
function find_user_by_id(mysqli $db, int $user_id): ?array {
  $sql = 'SELECT user_id, email
          FROM users
          WHERE user_id = ?';

  $result = mysqli_execute_query($db, $sql, [$user_id]);
  return mysqli_fetch_assoc($result);
}
