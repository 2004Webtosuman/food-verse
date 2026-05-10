<?php
// select_method.php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/forgot_password_helper.php';

if (!isset($_SESSION['reset_user_id'])) {
    redirect('forgot_password.php');
}

// Admins should not see this page, they are forced to Email OTP
if ($_SESSION['reset_role'] === 'admin') {
    redirect('forgot_password.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $userId = $_SESSION['reset_user_id'];
    $otp = generateOTP();

    if (storeOTP($pdo, $userId, $otp, $type)) {
        if ($type === 'email') {
            if (sendEmailOTP($_SESSION['reset_email'], $_SESSION['reset_name'], $otp)) {
                $_SESSION['otp_type'] = 'email';
                $_SESSION['last_otp_sent'] = time();
                redirect('verify_otp.php');
            }
        } else {
            // SMS
            if (sendSMSOTP($_SESSION['reset_phone'], $otp)) {
                $_SESSION['otp_type'] = 'sms';
                $_SESSION['last_otp_sent'] = time();
                redirect('verify_otp.php');
            }
        }
    }
    $error = 'Failed to send verification code. Please try again.';
}

$last4Phone = $_SESSION['reset_phone'] ? substr($_SESSION['reset_phone'], -4) : null;
$emailParts = explode('@', $_SESSION['reset_email']);
$maskedEmail = substr($emailParts[0], 0, 2) . '***@' . $emailParts[1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header_meta.php'; ?>
    <title>Select Method - FoodVerse</title>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .method-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .method-card:hover {
            transform: scale(1.02);
            border-color: #FF6B35;
            background: rgba(255, 107, 53, 0.05);
        }
    </style>
</head>
<body class="bg-app-bg min-h-screen flex flex-col items-center px-8 py-20 font-outfit">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-gray-900"><span class="text-primary">Food</span>Verse</h1>
            <p class="text-gray-500 mt-2 font-medium">Choose verification method</p>
        </div>

        <div class="glass-card rounded-[32px] p-10">
            <h2 class="text-2xl font-black text-gray-900 text-center mb-8">Where should we send the code?</h2>

            <?php if ($error): ?>
                <div class="bg-primary/5 border border-primary/10 text-primary p-4 rounded-2xl mb-8 text-sm flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>

            <form action="select_method.php" method="POST" class="space-y-4">
                <!-- Email Method -->
                <button type="submit" name="type" value="email" class="method-card w-full text-left p-6 bg-white border border-card-border rounded-2xl flex items-center gap-5">
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                        <i data-lucide="mail" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Via Email</p>
                        <p class="text-gray-800 font-black"><?php echo $maskedEmail; ?></p>
                    </div>
                    <i data-lucide="chevron-right" class="ml-auto w-5 h-5 text-gray-300"></i>
                </button>

                <!-- SMS Method -->
                <?php if ($_SESSION['reset_phone']): ?>
                <button type="submit" name="type" value="sms" class="method-card w-full text-left p-6 bg-white border border-card-border rounded-2xl flex items-center gap-5">
                    <div class="w-12 h-12 bg-secondary/10 rounded-xl flex items-center justify-center text-secondary">
                        <i data-lucide="message-square" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Via SMS</p>
                        <p class="text-gray-800 font-black">*******<?php echo $last4Phone; ?></p>
                    </div>
                    <i data-lucide="chevron-right" class="ml-auto w-5 h-5 text-gray-300"></i>
                </button>
                <?php endif; ?>
            </form>

            <p class="text-center text-gray-400 text-xs mt-10 font-medium">Verify your identity to proceed to password reset.</p>
        </div>

        <div class="mt-10 text-center">
            <a href="forgot_password.php" class="text-sm font-bold text-gray-500 hover:text-primary transition-colors flex items-center justify-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Change Email
            </a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
