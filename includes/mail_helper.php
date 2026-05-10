<?php
// includes/mail_helper.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Common function to send emails via PHPMailer
 */
function sendMail($to, $subject, $body, $attachmentPaths = [], $attachmentNames = []) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // Attachments
        if (!empty($attachmentPaths) && is_array($attachmentPaths)) {
            foreach ($attachmentPaths as $index => $path) {
                if ($path && file_exists($path)) {
                    $name = $attachmentNames[$index] ?? '';
                    $mail->addAttachment($path, $name);
                }
            }
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Trigger order-based emails
 */
function triggerOrderEmail($orderId, $type) {
    global $pdo;

    try {
        // Fetch order details (Customer + Rider)
        $stmt = $pdo->prepare("SELECT o.*, 
                               u.full_name as customer_name, u.email as customer_email,
                               r.full_name as rider_name, r.email as rider_email
                               FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               LEFT JOIN users r ON o.delivery_user_id = r.id
                               WHERE o.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) return false;

        // Fetch order items
        $itemStmt = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $itemStmt->execute([$orderId]);
        $items = $itemStmt->fetchAll();

        $primaryColor = '#FF6B35';
        $secondaryColor = '#6C5CE7';
        $appBg = '#FFF8F2';
        $sectionBg = '#FDF2EC';

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "<tr>
                <td style='padding:15px 0; border-bottom:1px solid #EEE; font-size:15px; font-weight: 800; color: #1A1A1A;'>{$item['product_name']}</td>
                <td style='padding:15px 0; border-bottom:1px solid #EEE; text-align:center; color: {$secondaryColor}; font-size: 14px; font-weight: 600;'>x{$item['quantity']}</td>
                <td style='padding:15px 0; border-bottom:1px solid #EEE; text-align:right; color: {$primaryColor}; font-size: 15px; font-weight: 800;'>Rs. ".number_format($item['price'] * $item['quantity'])."</td>
            </tr>";
        }

        $timestamp = date('M d, Y h:i A', strtotime($order['created_at']));
        $statusLabel = strtoupper(str_replace('_', ' ', $order['status']));
        $paymentStatus = ($order['status'] === 'paid' || $order['status'] === 'delivered' || $order['status'] === 'preparing' || $order['status'] === 'confirmed') ? 'PAID' : 'PENDING';
        $paymentMethod = strtoupper($order['payment_method'] ?? 'COD');
        $deliveryAddress = $order['delivery_address'] ?? 'Kathmandu, Nepal';

        // Common HTML skeleton based on the provided design
        $baseTemplate = "
        <div style='font-family: Arial, sans-serif; background-color: {$appBg}; padding: 60px 20px;'>
            <!-- Logo Section -->
            <div style='text-align: center; margin-bottom: 30px;'>
                <h1 style='color: #1A1A1A; margin: 0; font-size: 32px; font-weight: 900;'><span style='color: {$primaryColor};'>Food</span>Express</h1>
            </div>

            <!-- Main White Card -->
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 40px; padding: 50px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); text-align: center;'>
                
                <h2 style='color: #1A1A1A; font-size: 28px; margin-bottom: 20px; font-weight: 900; letter-spacing: -1px;'>{{TITLE}}</h2>
                
                <p style='color: #555; font-size: 16px; margin: 0;'>Hi <strong style='color: #1A1A1A;'>{{NAME}}</strong>,</p>
                <p style='color: #888; font-size: 14px; margin-top: 8px;'>{{DESCRIPTION}}</p>

                <!-- Order Info Box -->
                <div style='background-color: #F8F7F4; border-radius: 25px; padding: 30px; margin: 40px 0; display: table; width: 100%; box-sizing: border-box;'>
                    <div style='display: table-cell; text-align: left; width: 50%;'>
                        <p style='color: #BBB; font-size: 10px; font-weight: 900; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>ORDER ID</p>
                        <p style='color: #1A1A1A; font-size: 20px; font-weight: 900; margin: 8px 0 0 0;'>#FE-{$orderId}</p>
                    </div>
                    <div style='display: table-cell; text-align: right; width: 50%; vertical-align: bottom;'>
                        <p style='color: #BBB; font-size: 10px; font-weight: 900; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>DATE</p>
                        <p style='color: #777; font-size: 12px; font-weight: bold; margin: 8px 0 0 0;'>{$timestamp}</p>
                    </div>
                </div>

                <!-- Product Table -->
                <table style='width: 100%; margin-bottom: 40px; border-collapse: collapse;'>
                    <thead>
                        <tr style='color: #BBB; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;'>
                            <th style='text-align: left; padding: 15px 0;'>ITEM</th>
                            <th style='text-align: center; padding: 15px 0;'>QTY</th>
                            <th style='text-align: right; padding: 15px 0;'>PRICE</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>

                <!-- Large Total Box -->
                <div style='background-color: {$primaryColor}; border-radius: 25px; padding: 35px; margin-bottom: 40px; color: #ffffff;'>
                    <p style='font-size: 11px; font-weight: 900; text-transform: uppercase; margin: 0; opacity: 0.9; letter-spacing: 1px;'>TOTAL AMOUNT</p>
                    <p style='font-size: 48px; font-weight: 900; margin: 10px 0 0 0;'>Rs. ".number_format($order['total_price'])."</p>
                </div>

                <!-- Footer Details -->
                <div style='text-align: left;'>
                    <div style='display: table; width: 100%; margin-bottom: 18px;'>
                        <span style='display: table-cell; color: #BBB; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;'>PAYMENT</span>
                        <span style='display: table-cell; text-align: right; color: #1A1A1A; font-size: 14px; font-weight: 900;'>{$paymentMethod} ({$paymentStatus})</span>
                    </div>
                    <div style='display: table; width: 100%;'>
                        <span style='display: table-cell; color: #BBB; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;'>DELIVERY TO</span>
                        <span style='display: table-cell; text-align: right; color: #1A1A1A; font-size: 14px; font-weight: 900;'>{$deliveryAddress}</span>
                    </div>
                </div>
            </div>

            <!-- Footer Logo -->
            <div style='text-align: center; margin-top: 40px; color: #AAA; font-size: 12px; font-weight: 600;'>
                Made with <span style='color: {$primaryColor};'>❤️</span> by FoodExpress
            </div>
        </div>";

        switch ($type) {
            case 'new_order':
                // To User
                $uBody = str_replace(['{{TITLE}}', '{{NAME}}', '{{DESCRIPTION}}'], ['Order Confirmed! 🥳', $order['customer_name'], 'Thank you for your order! We\'re preparing your food with love.'], $baseTemplate);
                sendMail($order['customer_email'], "Order Placed - #FE-{$orderId}", $uBody);
                
                // To Admin
                $aBody = str_replace(['{{TITLE}}', '{{NAME}}', '{{DESCRIPTION}}'], ['New Order Alert! 🔔', 'Admin', "A new order has been placed by {$order['customer_name']}. Please check the dashboard to confirm it."], $baseTemplate);
                sendMail(ADMIN_EMAIL, "New Order Received - #FE-{$orderId}", $aBody);
                break;

            case 'status_update':
                // To User
                $title = "Order Updated! 🚀";
                $msg = "Your order status has been updated to {$statusLabel}.";
                if ($order['status'] === 'confirmed') {
                    $title = "Order Confirmed! 🥳";
                    $msg = "Great news! Your order has been confirmed and is now being prepared with love.";
                }
                if ($order['status'] === 'out_for_delivery') {
                    $title = "On the Way! 🏍️";
                    $msg = "Hold tight! Your delicious meal is on the way to your doorstep right now.";
                }
                if ($order['status'] === 'delivered') {
                    $title = "Enjoy Your Meal! 🍽️";
                    $msg = "Your order has been successfully delivered. We hope you enjoy every bite!";
                }
                
                $uBody = str_replace(['{{TITLE}}', '{{NAME}}', '{{DESCRIPTION}}'], [$title, $order['customer_name'], $msg], $baseTemplate);
                sendMail($order['customer_email'], "Order Update - #FE-{$orderId}", $uBody);
                
                // To Admin (Log)
                $adminTitle = "Status Update Sync 📋";
                $aBody = str_replace(['{{TITLE}}', '{{NAME}}', '{{DESCRIPTION}}'], [$adminTitle, 'Admin', "Order #{$orderId} status was updated to {$statusLabel}."], $baseTemplate);
                sendMail(ADMIN_EMAIL, "Status Sync: Order #{$orderId} is now {$statusLabel}", $aBody);
                
                // To Rider (Specific or Hub)
                if ($order['status'] === 'confirmed') {
                    $riderTitle = "New Task Waiting! 📦";
                    $rBody = str_replace(['{{TITLE}}', '{{NAME}}', '{{DESCRIPTION}}'], [$riderTitle, 'Rider Team', "A new order #{$orderId} has been confirmed. Please proceed to the restaurant for pickup."], $baseTemplate);
                    sendMail(RIDER_EMAIL, "New Delivery Assigned - #FE-{$orderId}", $rBody);
                } elseif ($order['status'] === 'delivered' && !empty($order['rider_email'])) {
                    $riderTitle = "Mission Accomplished! 🏆";
                    $rMsg = "Great job! You've successfully delivered order #{$orderId}. Thank you for your hard work and dedication to FoodVerse.";
                    $rBody = str_replace(['{{TITLE}}', '{{NAME}}', '{{DESCRIPTION}}'], [$riderTitle, $order['rider_name'], $rMsg], $baseTemplate);
                    sendMail($order['rider_email'], "Thank You for Delivery - #FE-{$orderId}", $rBody);
                }
                break;
        }


        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Trigger product review notifications
 */
function triggerReviewEmail($reviewId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT pr.*, u.full_name as customer_name, u.email as customer_email, p.name as product_name 
                               FROM product_reviews pr 
                               JOIN users u ON pr.user_id = u.id 
                               JOIN products p ON pr.product_id = p.id 
                               WHERE pr.id = ?");
        $stmt->execute([$reviewId]);
        $review = $stmt->fetch();
        if (!$review) return false;

        $primaryColor = '#FF6B35';
        $appBg = '#FFF8F2';
        
        $stars = str_repeat('⭐', $review['rating']);
        
        $bodyTemplate = "
        <div style='font-family: Arial, sans-serif; background-color: $appBg; padding: 60px 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 40px; padding: 50px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); text-align: center;'>
                <h1 style='color: #1A1A1A; margin: 0; font-size: 32px; font-weight: 900;'><span style='color: $primaryColor;'>Food</span>Express</h1>
                <h2 style='color: #1A1A1A; font-size: 24px; margin: 30px 0 20px; font-weight: 900;'>{{TITLE}}</h2>
                <p style='color: #555; font-size: 16px;'>Hi <strong>{{NAME}}</strong>,</p>
                <p style='color: #888; font-size: 14px; margin-bottom: 30px;'>{{DESCRIPTION}}</p>
                
                <div style='background-color: #F8F7F4; border-radius: 25px; padding: 30px; text-align: left;'>
                    <p style='margin: 0; font-size: 10px; color: #BBB; font-weight: 900; text-transform: uppercase;'>Product</p>
                    <p style='margin: 5px 0 15px; font-size: 16px; font-weight: 900; color: #1A1A1A;'>{$review['product_name']}</p>
                    <p style='margin: 0; font-size: 10px; color: #BBB; font-weight: 900; text-transform: uppercase;'>Rating</p>
                    <p style='margin: 5px 0 15px; font-size: 20px;'>$stars</p>
                    <p style='margin: 0; font-size: 10px; color: #BBB; font-weight: 900; text-transform: uppercase;'>Comment</p>
                    <p style='margin: 5px 0 0; font-size: 14px; color: #555; font-style: italic;'>\"{$review['review_text']}\"</p>
                </div>
            </div>
        </div>";

        // To User
        $uBody = str_replace(['{{TITLE}}', '{{NAME}}', '{{DESCRIPTION}}'], ['Review Shared! ✨', $review['customer_name'], 'Thank you for sharing your experience! Your review helps others make better choices.'], $bodyTemplate);
        sendMail($review['customer_email'], "Review Confirmation - {$review['product_name']}", $uBody);

        // To Admin
        $aBody = str_replace(['{{TITLE}}', '{{NAME}}', '{{DESCRIPTION}}'], ['New Product Review! 📣', 'Admin', "A new review has been posted for {$review['product_name']} by {$review['customer_name']}."], $bodyTemplate);
        sendMail(ADMIN_EMAIL, "New Review Alert: {$review['product_name']}", $aBody);

        return true;
    } catch (Exception $e) { return false; }
}

/**
 * Trigger system feedback notifications
 */
function triggerFeedbackEmail($feedbackId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT sf.*, u.full_name as customer_name, u.email as customer_email FROM system_feedback sf JOIN users u ON sf.user_id = u.id WHERE sf.id = ?");
        $stmt->execute([$feedbackId]);
        $feedback = $stmt->fetch();
        if (!$feedback) return false;

        $primaryColor = '#6C5CE7'; // Use purple for system-wide feedback
        $appBg = '#FFF8F2';
        $stars = str_repeat('⭐', $feedback['rating']);

        $bodyTemplate = "
        <div style='font-family: Arial, sans-serif; background-color: $appBg; padding: 60px 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 40px; padding: 50px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); text-align: center;'>
                <h1 style='color: #1A1A1A; margin: 0; font-size: 32px; font-weight: 900;'><span style='color: #FF6B35;'>Food</span>Express</h1>
                <h2 style='color: #1A1A1A; font-size: 24px; margin: 30px 0 20px; font-weight: 900;'>{{TITLE}}</h2>
                <p style='color: #555; font-size: 16px;'>Hi <strong>{{NAME}}</strong>,</p>
                <p style='color: #888; font-size: 14px; margin-bottom: 30px;'>{{DESCRIPTION}}</p>
                
                <div style='background-color: #F8F7F4; border-radius: 25px; padding: 30px; text-align: left;'>
                    <p style='margin: 0; font-size: 10px; color: #BBB; font-weight: 900; text-transform: uppercase;'>Experience Rating</p>
                    <p style='margin: 5px 0 15px; font-size: 20px;'>$stars</p>
                    <p style='margin: 0; font-size: 10px; color: #BBB; font-weight: 900; text-transform: uppercase;'>Feedback</p>
                    <p style='margin: 5px 0 0; font-size: 14px; color: #555; font-style: italic;'>\"{$feedback['feedback_text']}\"</p>
                </div>
            </div>
        </div>";

        // To User
        $uBody = str_replace(['{{TITLE}}', '{{NAME}}', '{{DESCRIPTION}}'], ['Feedback Received! ❤️', $feedback['customer_name'], 'We appreciate you taking the time to share your feedback. We are constantly working to improve!'], $bodyTemplate);
        sendMail($feedback['customer_email'], "Feedback Received - FoodVerse", $uBody);

        // To Admin
        $aBody = str_replace(['{{TITLE}}', '{{NAME}}', '{{DESCRIPTION}}'], ['General Feedback Alert! 💡', 'Admin', "General system feedback has been received from {$feedback['customer_name']}."], $bodyTemplate);
        sendMail(ADMIN_EMAIL, "System Feedback Received", $aBody);

        return true;
    } catch (Exception $e) { return false; }
}
?>
