<?php
declare(strict_types=1);

// Log out: end the session, then go back to the login page.
// Written with help from Claude (AI). See README, "AI use".

require __DIR__ . '/../includes/auth.php';

log_out_user();

header('Location: login.php?logged_out=1');
exit;
