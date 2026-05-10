<?php
// admin/actions/edit_category.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_admin()) redirect('../../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $name = sanitize($_POST['name']);

    if (!empty($name) && $id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            $_SESSION['flash_message'] = "Category updated successfully.";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_message'] = "Invalid input.";
    }
}

redirect('../categories.php');
