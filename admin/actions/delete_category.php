<?php
// admin/actions/delete_category.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_admin()) redirect('../../login.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // Prevent deleting if products still use it, or you could do CASCADE based on db schema. 
        // For safety, let's just delete the category.
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = "Category deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Cannot delete category (maybe products are linked to it). Error: " . $e->getMessage();
    }
}

redirect('../categories.php');
