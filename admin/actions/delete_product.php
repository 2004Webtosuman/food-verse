<?php
// admin/actions/delete_product.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_admin()) {
    $_SESSION['flash_message'] = "Access denied.";
    redirect('../../login.php');
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['flash_message'] = "Product deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error deleting product: " . $e->getMessage();
    }
}

redirect('../products.php');
