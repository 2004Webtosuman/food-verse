<?php
// logout.php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

logout_user();
redirect('login.php');
?>
