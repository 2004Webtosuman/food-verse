<?php
// forgot_password.php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/forgot_password_helper.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $user = checkUserByEmail($pdo, $email);

    if ($user) {
        $_SESSION['reset_user_id'] = $user['id'];
        $_SESSION['reset_email'] = $user['email'];
        $_SESSION['reset_role'] = $user['role'];
        $_SESSION['reset_name'] = $user['full_name'];
        $_SESSION['reset_phone'] = $user['phone'];

        // Force Email OTP for ALL roles for now
        $otp = generateOTP();
        if (storeOTP($pdo, $user['id'], $otp, 'email')) {
            if (sendEmailOTP($user['email'], $user['full_name'], $otp)) {
                $_SESSION['otp_type'] = 'email';
                $_SESSION['last_otp_sent'] = time();
                redirect('verify_otp.php');
            } else {
                $error = 'Failed to send verification email. Please try again.';
            }
        }
    } else {
        // Prevent enumerations
        $error = 'If an account exists with this email, we have sent instructions to reset your password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header_meta.php'; ?>
    <title>Forgot Password - FoodVerse</title>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(255, 107, 53, 0.05);
        }
        .neon-btn {
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.2);
            transition: all 0.3s ease;
        }
        .neon-btn:hover {
            box-shadow: 0 0 30px rgba(255, 107, 53, 0.4);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-app-bg min-h-screen flex flex-col items-center px-8 py-20 font-outfit">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-gray-900"><span class="text-primary">Food</span>Verse</h1>
            <p class="text-gray-500 mt-2 font-medium">Reset your account access</p>
        </div>

        <div class="glass-card rounded-[32px] p-10">
            <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mb-8 mx-auto">
                <i data-lucide="key-round" class="w-8 h-8 text-primary"></i>
            </div>
            
            <h2 class="text-2xl font-black text-gray-900 text-center mb-2">Forgot Password?</h2>
            <p class="text-gray-500 text-center text-sm mb-10 leading-relaxed">Enter your registered email below. We'll send you a verification code to reset your password.</p>

            <?php if ($error): ?>
                <div class="bg-primary/5 border border-primary/10 text-primary p-4 rounded-2xl mb-8 text-sm flex items-center gap-3">
                    <i data-lucide="info" class="w-5 h-5 flex-shrink-0"></i>
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST" class="space-y-6">
                <div class="space-y-3">
                    <label class="block text-gray-600 font-bold ml-1 text-sm uppercase tracking-wider">Email Address</label>
                    <div class="relative group">
                        <i data-lucide="mail" class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                        <input type="email" name="email" placeholder="suman@example.com" required
                            class="w-full pl-14 pr-5 py-5 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none focus:ring-4 focus:ring-primary/10 transition-all text-gray-800 placeholder:text-gray-400 font-medium">
                    </div>
                </div>

                <button type="submit" class="w-full py-5 bg-primary text-white font-black rounded-3xl neon-btn active:scale-95 text-lg shadow-lg tracking-widest uppercase">
                    Get Verification Code
                </button>
            </form>
        </div>

        <div class="mt-10 text-center">
            <a href="login.php" class="text-sm font-bold text-gray-500 hover:text-primary transition-colors flex items-center justify-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to Login
            </a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
