<?php
// admin/actions/add_product.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_admin()) {
    $_SESSION['flash_message'] = "Access denied.";
    redirect('../../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $description = sanitize($_POST['description']);
    $is_deal = isset($_POST['is_deal']) ? 1 : 0;
    $veg_type = sanitize($_POST['veg_type'] ?? 'veg');

    if ($stock < 0) {
        $_SESSION['flash_message'] = "Stock quantity cannot be negative.";
        redirect('../products.php');
    }

    // Handle Image Upload
    $image_url = 'images/placeholder.png'; // Default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_url = 'uploads/' . $file_name;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, image_url, stock_quantity, is_deal, veg_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $description, $price, $image_url, $stock, $is_deal, $veg_type]);
        
        $_SESSION['flash_message'] = "Product added successfully!";
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error adding product: " . $e->getMessage();
    }

    redirect('../products.php');
}
