<?php
// reset_password.php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/forgot_password_helper.php';

if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_verified'])) {
    redirect('forgot_password.php');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        if (resetPassword($pdo, $_SESSION['reset_user_id'], $password)) {
            $success = true;
            // Clear reset sessions
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_role']);
            unset($_SESSION['otp_type']);
            unset($_SESSION['otp_verified']);
            unset($_SESSION['last_otp_sent']);
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header_meta.php'; ?>
    <title>Reset Password - FoodVerse</title>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .success-box {
            animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-app-bg min-h-screen flex flex-col items-center px-8 py-20 font-outfit">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-gray-900"><span class="text-primary">Food</span>Verse</h1>
            <p class="text-gray-500 mt-2 font-medium">Secure your account</p>
        </div>

        <div class="glass-card rounded-[32px] p-10">
            <?php if ($success): ?>
                <div class="success-box text-center">
                    <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mb-8 mx-auto">
                        <i data-lucide="check-circle" class="w-10 h-10 text-green-500"></i>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 mb-2">Password Reset!</h2>
                    <p class="text-gray-500 text-sm mb-10">Your password has been successfully updated. You can now log in with your new credentials.</p>
                    <a href="login.php" class="block w-full py-5 bg-primary text-white font-black rounded-3xl text-lg shadow-lg tracking-widest uppercase hover:bg-primary-hover transition-all">
                        Login Now
                    </a>
                </div>
            <?php else: ?>
                <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mb-8 mx-auto">
                    <i data-lucide="lock" class="w-8 h-8 text-primary"></i>
                </div>
                
                <h2 class="text-2xl font-black text-gray-900 text-center mb-2">Set New Password</h2>
                <p class="text-gray-500 text-center text-sm mb-10 leading-relaxed">Please create a strong password that you haven't used before.</p>

                <?php if ($error): ?>
                    <div class="bg-primary/5 border border-primary/10 text-primary p-4 rounded-2xl mb-8 text-sm flex items-center gap-3">
                        <i data-lucide="info" class="w-5 h-5 flex-shrink-0"></i>
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>

                <form action="reset_password.php" method="POST" class="space-y-6">
                    <div class="space-y-3">
                        <label class="block text-gray-600 font-bold ml-1 text-sm uppercase tracking-wider">New Password</label>
                        <div class="relative group">
                            <i data-lucide="lock" class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                            <input type="password" id="newPass" name="password" placeholder="••••••••" required
                                class="w-full pl-14 pr-12 py-5 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none focus:ring-4 focus:ring-primary/10 transition-all text-gray-800 font-medium">
                            <button type="button" onclick="togglePass('newPass', this)" class="absolute inset-y-0 right-1 pr-4 flex items-center hover:text-primary transition-colors">
                                <i data-lucide="eye-off" class="w-5 h-5 text-gray-400"></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-gray-600 font-bold ml-1 text-sm uppercase tracking-wider">Confirm Password</label>
                        <div class="relative group">
                            <i data-lucide="check-circle-2" class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                            <input type="password" id="confPass" name="confirm_password" placeholder="••••••••" required
                                class="w-full pl-14 pr-12 py-5 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none focus:ring-4 focus:ring-primary/10 transition-all text-gray-800 font-medium">
                            <button type="button" onclick="togglePass('confPass', this)" class="absolute inset-y-0 right-1 pr-4 flex items-center hover:text-primary transition-colors">
                                <i data-lucide="eye-off" class="w-5 h-5 text-gray-400"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-5 bg-primary text-white font-black rounded-3xl active:scale-95 text-lg shadow-lg tracking-widest uppercase mt-4">
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function togglePass(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye-off');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>
