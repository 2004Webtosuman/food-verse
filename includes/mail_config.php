<?php
// includes/mail_config.php

// SMTP Configuration
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_PORT', getenv('MAIL_PORT') ?: 587);
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '2004sumanmishra@gmail.com');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: 'kulrrsxyztlzwgey'); // Local default, overridden on Render
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: '2004sumanmishra@gmail.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'FoodVerse');

// Role-based Emails (Default test emails)
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'mishrasumane@gmail.com');
define('RIDER_EMAIL', getenv('RIDER_EMAIL') ?: '2024wildluffy@gmail.com');
define('USER_EMAIL_DEFAULT', getenv('USER_EMAIL_DEFAULT') ?: '2004sumanmishra@gmail.com');
?>
