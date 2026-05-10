<?php
// actions/esewa_failure.php
require_once '../includes/functions.php';

session_start();
$_SESSION['flash_message'] = "Payment was cancelled or failed. Please try again.";
header("Location: ../customer/checkout.php?step=2");
exit;
