<?php
// signup.php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $re_password = $_POST['re_password'];
    $role = isset($_POST['role']) ? sanitize($_POST['role']) : 'user';
    
    if ($password !== $re_password) {
        $error = 'Passwords do not match.';
    } else {
        $province = sanitize($_POST['province'] ?? '');
        $district = sanitize($_POST['district'] ?? '');
        $municipality = sanitize($_POST['municipality'] ?? '');

        if (register_user($pdo, $full_name, $email, $password, $role, $province, $district, $municipality)) {
            $success = 'Account created successfully! You can now login.';
        } else {
            $error = 'Email already exists or registration failed.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header_meta.php'; ?>
    <title>Create Account - FoodVerse</title>
</head>
<body class="bg-app-bg min-h-screen flex flex-col items-center px-8 py-10 font-outfit">

    <!-- Top Decoration -->
    <div class="w-24 h-24 bg-white rounded-full mb-8 flex items-center justify-center p-3 border border-card-border shadow-md">
        <div class="w-full h-full bg-primary/10 rounded-full flex items-center justify-center">
            <i data-lucide="user-plus" class="w-10 h-10 text-primary"></i>
        </div>
    </div>

    <!-- Title -->
    <h1 class="text-3xl font-black mb-1 text-gray-900">Create Account</h1>
    <p class="text-gray-500 text-sm mb-8 font-medium tracking-wide">Enter your details to sign up</p>

    <?php if ($error): ?>
        <div class="w-full max-w-sm bg-red-50 border border-red-200 text-red-600 p-4 rounded-2xl mb-6 text-sm flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="w-full max-w-sm bg-green-50 border border-green-200 text-green-600 p-4 rounded-2xl mb-6 text-sm flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Signup Form -->
    <form action="signup.php" method="POST" class="w-full max-w-sm space-y-5">
        <div class="space-y-2">
            <label class="block text-gray-500 font-bold ml-1 text-xs uppercase tracking-widest">Full Name</label>
            <div class="relative group">
                <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                <input type="text" name="full_name" placeholder="John Doe" required
                    class="w-full pl-12 pr-4 py-4 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none transition-all text-gray-800 placeholder:text-gray-400">
            </div>
        </div>

        <div class="space-y-2">
            <label class="block text-gray-500 font-bold ml-1 text-xs uppercase tracking-widest">Email</label>
            <div class="relative group">
                <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                <input type="email" name="email" placeholder="example123@gmail.com" required
                    class="w-full pl-12 pr-4 py-4 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none transition-all text-gray-800 placeholder:text-gray-400">
            </div>
        </div>

        <!-- Role Selection -->
        <div class="space-y-2">
            <label class="block text-gray-500 font-bold ml-1 text-xs uppercase tracking-widest">I want to</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="role" value="user" checked class="hidden peer">
                    <div class="peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary bg-white border border-card-border rounded-2xl p-4 text-center transition-all hover:border-primary/30 text-gray-600">
                        <i data-lucide="shopping-bag" class="w-6 h-6 mx-auto mb-2"></i>
                        <span class="text-xs font-bold uppercase tracking-wider">Order Food</span>
                        <p class="text-[9px] text-gray-400 mt-1">Customer</p>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="role" value="delivery" class="hidden peer">
                    <div class="peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary bg-white border border-card-border rounded-2xl p-4 text-center transition-all hover:border-primary/30 text-gray-600">
                        <i data-lucide="bike" class="w-6 h-6 mx-auto mb-2"></i>
                        <span class="text-xs font-bold uppercase tracking-wider">Deliver Food</span>
                        <p class="text-[9px] text-gray-400 mt-1">Rider</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="space-y-2">
            <label class="block text-gray-500 font-bold ml-1 text-xs uppercase tracking-widest">Password</label>
            <div class="relative group">
                <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                <input type="password" id="password" name="password" placeholder="••••••••" required
                    class="w-full pl-12 pr-12 py-4 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none transition-all text-gray-800 placeholder:text-gray-400">
                <button type="button" onclick="togglePass('password', this)" class="absolute inset-y-0 right-0 pr-4 flex items-center hover:text-primary transition-colors">
                    <i data-lucide="eye-off" class="w-5 h-5 text-gray-400"></i>
                </button>
            </div>
        </div>

        <div class="space-y-2">
            <label class="block text-gray-500 font-bold ml-1 text-xs uppercase tracking-widest">Re-type password</label>
            <div class="relative group">
                <i data-lucide="shield-check" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                <input type="password" id="re_password" name="re_password" placeholder="••••••••" required
                    class="w-full pl-12 pr-12 py-4 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none transition-all text-gray-800 placeholder:text-gray-400">
                <button type="button" onclick="togglePass('re_password', this)" class="absolute inset-y-0 right-0 pr-4 flex items-center hover:text-primary transition-colors">
                    <i data-lucide="eye-off" class="w-5 h-5 text-gray-400"></i>
                </button>
            </div>
        </div>

        <!-- Location Selection -->
        <div class="space-y-2">
            <label class="block text-gray-500 font-bold ml-1 text-xs uppercase tracking-widest">Delivery Location</label>
            <button type="button" id="btn-open-location" class="w-full text-left p-4 bg-white border border-card-border focus:border-primary/40 rounded-2xl flex items-center justify-between group transition-all">
                <div class="flex items-center gap-3">
                    <i data-lucide="map-pin" class="w-4 h-4 text-primary group-hover:scale-110 transition-transform"></i>
                    <span id="display-location" class="text-xs text-gray-400 font-medium">Select Province, District & Palika</span>
                </div>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
            </button>
            <input type="hidden" name="province" id="input-province" required>
            <input type="hidden" name="district" id="input-district" required>
            <input type="hidden" name="municipality" id="input-municipality" required>
        </div>

        <button type="submit" class="w-full py-5 bg-primary text-white font-black rounded-3xl hover:bg-primary-hover hover:shadow-lg transition-all active:scale-95 shadow-md mt-6 tracking-widest uppercase">
            Create Account
        </button>
    </form>

    <!-- Login Link -->
    <p class="mt-10 text-sm text-gray-500 font-medium">
        Already have an account? <a href="login.php" class="font-black text-primary hover:text-primary-hover transition-colors border-b-2 border-primary/30">Login</a>
    </p>

    <!-- Location Modal Overlay (Copy of index.php modal for signup) -->
    <div id="location-modal-overlay" class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center sm:p-4 opacity-0 pointer-events-none transition-all duration-300">
        <div id="location-modal-content" class="w-full sm:max-w-md bg-white text-gray-900 rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl border-t sm:border border-card-border transform translate-y-full sm:translate-y-0 sm:scale-95 transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold tracking-tight text-gray-900">Delivery Location</h3>
                <button type="button" id="btn-close-location" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition-all">
                    <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                </button>
            </div>
            <div id="location-picker-container"></div>
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

        // Location Picker Initialization
        const locationModal = document.getElementById('location-modal-overlay');
        const locationContent = document.getElementById('location-modal-content');
        const btnOpenLocation = document.getElementById('btn-open-location');
        const btnCloseLocation = document.getElementById('btn-close-location');
        const displayLocation = document.getElementById('display-location');
        
        const inputProv = document.getElementById('input-province');
        const inputDist = document.getElementById('input-district');
        const inputMuni = document.getElementById('input-municipality');

        const locPicker = new LocationPicker('location-picker-container', {
            onSave: (location) => {
                displayLocation.innerText = location.full_address;
                displayLocation.classList.remove('text-gray-400');
                displayLocation.classList.add('text-gray-800');
                
                inputProv.value = location.province;
                inputDist.value = location.district;
                inputMuni.value = location.municipality;
                
                closeLocationModal();
            }
        });

        function openLocationModal() {
            locationModal.classList.remove('opacity-0', 'pointer-events-none');
            locationContent.classList.remove('translate-y-full', 'sm:scale-95');
            document.body.style.overflow = 'hidden';
        }

        function closeLocationModal() {
            locationModal.classList.add('opacity-0', 'pointer-events-none');
            locationContent.classList.add('translate-y-full', 'sm:scale-95');
            document.body.style.overflow = '';
        }

        btnOpenLocation.addEventListener('click', openLocationModal);
        btnCloseLocation.addEventListener('click', (e) => {
            e.preventDefault();
            closeLocationModal();
        });

        locationModal.addEventListener('click', (e) => {
            if (e.target === locationModal) closeLocationModal();
        });
    </script>
</body>
</html>
