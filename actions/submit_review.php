<?php
// actions/submit_review.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/mail_helper.php';

// Security: User must be logged in
if (!is_logged_in()) {
    $_SESSION['error'] = "Please login to submit a review.";
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    $rating = (int)$_POST['rating'];
    $review_text = sanitize($_POST['review_text']);

    if ($product_id <= 0 || $rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Invalid review data.";
        header("Location: ../product.php?id=$product_id");
        exit();
    }

    try {
        // Prevent multiple reviews for same product by same user
        $checkStmt = $pdo->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$user_id, $product_id]);
        if ($checkStmt->fetch()) {
            $_SESSION['error'] = "You have already reviewed this product.";
            header("Location: ../product.php?id=$product_id");
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO product_reviews (user_id, product_id, rating, review_text) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $product_id, $rating, $review_text])) {
            $reviewId = $pdo->lastInsertId();
            
            // Trigger Emails
            triggerReviewEmail($reviewId);
            
            $_SESSION['success'] = "Thank you for your review! It has been posted.";
        } else {
            $_SESSION['error'] = "Failed to submit review. Please try again.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: ../product.php?id=$product_id");
    exit();
}
?>
