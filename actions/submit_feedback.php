<?php
// actions/submit_feedback.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/mail_helper.php';

// Security: User must be logged in
if (!is_logged_in()) {
    $_SESSION['error'] = "Please login to submit feedback.";
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $rating = (int)$_POST['rating'];
    $feedback_text = sanitize($_POST['feedback_text']);

    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Invalid rating scale.";
        header("Location: ../customer/feedback.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO system_feedback (user_id, rating, feedback_text) VALUES (?, ?, ?)");
        if ($stmt->execute([$user_id, $rating, $feedback_text])) {
            $feedbackId = $pdo->lastInsertId();
            
            // Trigger Emails
            triggerFeedbackEmail($feedbackId);
            
            $_SESSION['success'] = "Thank you! Your feedback has been received.";
        } else {
            $_SESSION['error'] = "Failed to submit feedback. Please try again.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: ../customer/feedback.php");
    exit();
}
?>
