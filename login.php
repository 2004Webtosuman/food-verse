<?php
// login.php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $login_role = $_POST['login_role'] ?? 'user';
    
    $auth_status = login_user($pdo, $email, $password);
    
    if ($auth_status === 'requires_2fa') {
        // Redirect to OTP verification page which will auto-send
        // We will send the OTP via AJAX when verify_otp.php loads, or we can send it right here.
        redirect('verify_otp.php');
    } else if ($auth_status === true) {
        $actual_role = $_SESSION['user_role'];
        
        // Role mismatch security check
        if ($login_role === 'admin' && $actual_role !== 'admin') {
            logout_user();
            $error = 'Access denied. This side is for administrators only.';
        } elseif ($login_role === 'user' && $actual_role === 'admin') {
            logout_user();
            $error = 'This account is for admins. Please use the Admin Login side.';
        } else {
            // Success - Redirect based on actual role
            if ($actual_role === 'admin') {
                redirect('admin/dashboard.php');
            } elseif ($actual_role === 'delivery') {
                redirect('delivery/dashboard.php');
            } else {
                redirect('index.php');
            }
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header_meta.php'; ?>
    <title>Login - FoodVerse</title>
</head>
<body class="bg-app-bg min-h-screen flex flex-col items-center px-8 py-16 font-outfit">

    <!-- Top Circle Decoration -->
    <div class="w-32 h-32 bg-white rounded-full mb-12 flex items-center justify-center p-4 border border-card-border shadow-md">
        <div class="w-full h-full bg-primary/10 rounded-full flex items-center justify-center">
            <i data-lucide="user" class="w-12 h-12 text-primary"></i>
        </div>
    </div>

    <!-- Welcome Text -->
    <div class="text-center">
        <h1 class="text-4xl font-black mb-2 text-gray-900">Welcome Back!</h1>
        <p class="text-gray-500 text-sm mb-10 font-medium tracking-wide">Enter your details to continue</p>
    </div>

    <!-- Role Toggle -->
    <div class="bg-white p-1.5 rounded-full flex items-center gap-1 border border-card-border shadow-sm mb-12 w-full max-w-[280px]">
        <button type="button" onclick="setRole('user')" id="userBtn" 
            class="flex-1 py-3 px-6 rounded-full text-sm font-bold transition-all duration-300 bg-primary text-white shadow-md">
            User Login
        </button>
        <button type="button" onclick="setRole('admin')" id="adminBtn" 
            class="flex-1 py-3 px-6 rounded-full text-sm font-bold transition-all duration-300 text-gray-400 hover:text-gray-600">
            Admin Login
        </button>
    </div>

    <?php if ($error): ?>
        <div class="w-full max-w-sm bg-red-50 border border-red-200 text-red-600 p-4 rounded-2xl mb-8 text-sm flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="login.php" method="POST" class="w-full max-w-sm space-y-8">
        <input type="hidden" name="login_role" id="login_role" value="user">
        
        <div class="space-y-3">
            <label class="block text-gray-600 font-bold ml-1 text-sm">Email Address</label>
            <div class="relative group">
                <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                <input type="email" name="email" placeholder="example123@gmail.com" required
                    class="w-full pl-12 pr-4 py-4 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none focus:ring-4 focus:ring-primary/10 transition-all text-gray-800 placeholder:text-gray-400">
            </div>
        </div>

        <div class="space-y-3">
            <label class="block text-gray-600 font-bold ml-1 text-sm">Password</label>
            <div class="relative group">
                <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                <input type="password" id="loginPass" name="password" placeholder="••••••••" required
                    class="w-full pl-12 pr-12 py-4 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none focus:ring-4 focus:ring-primary/10 transition-all text-gray-800 placeholder:text-gray-400">
                <button type="button" onclick="togglePass('loginPass', this)" class="absolute inset-y-0 right-0 pr-4 flex items-center hover:text-primary transition-colors">
                    <i data-lucide="eye-off" class="w-5 h-5 text-gray-400"></i>
                </button>
            </div>
            <div class="text-right">
                <a href="forgot_password.php" class="text-xs font-bold text-gray-500 hover:text-primary transition-colors">Forgot password?</a>
            </div>
        </div>

        <button type="submit" class="w-full py-5 bg-primary text-white font-black rounded-3xl hover:bg-primary-hover hover:shadow-lg transition-all active:scale-95 shadow-md mt-8 tracking-widest uppercase">
            Login
        </button>
    </form>

    <!-- Signup Link -->
    <p id="signup-link" class="mt-12 text-sm text-gray-500 font-medium transition-all duration-300">
        Don't have an account? <a href="signup.php" class="font-black text-primary hover:text-primary-hover transition-colors border-b-2 border-primary/30">Create account</a>
    </p>

    <script>
        lucide.createIcons();

        function setRole(role) {
            const roleInput = document.getElementById('login_role');
            const userBtn = document.getElementById('userBtn');
            const adminBtn = document.getElementById('adminBtn');
            const signupLink = document.getElementById('signup-link');

            roleInput.value = role;

            if (role === 'user') {
                userBtn.className = "flex-1 py-3 px-6 rounded-full text-sm font-bold transition-all duration-300 bg-primary text-white shadow-md";
                adminBtn.className = "flex-1 py-3 px-6 rounded-full text-sm font-bold transition-all duration-300 text-gray-400 hover:text-gray-600";
                signupLink.style.opacity = "1";
                signupLink.style.pointerEvents = "auto";
            } else {
                adminBtn.className = "flex-1 py-3 px-6 rounded-full text-sm font-bold transition-all duration-300 bg-primary text-white shadow-md";
                userBtn.className = "flex-1 py-3 px-6 rounded-full text-sm font-bold transition-all duration-300 text-gray-400 hover:text-gray-600";
                signupLink.style.opacity = "0";
                signupLink.style.pointerEvents = "none";
            }
        }

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
