<?php
// admin/actions/add_category.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_admin()) redirect('../../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $_SESSION['flash_message'] = "Category added successfully.";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_message'] = "Category name cannot be empty.";
    }
}

redirect('../categories.php');
