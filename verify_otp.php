<?php
// verify_otp.php
require_once 'includes/functions.php';

if (!isset($_SESSION['pending_2fa_user_id']) && !isset($_SESSION['reset_user_id'])) {
    redirect('login.php');
}

$is_reset = isset($_SESSION['reset_user_id']);
$target_email = $is_reset ? $_SESSION['reset_email'] : $_SESSION['pending_2fa_email'];
$email_masked = substr($target_email, 0, 3) . '***@' . explode('@', $target_email)[1];

// Check if OTP was recently sent (within last 30 seconds) to avoid redundant emails on page load
$recently_sent = isset($_SESSION['last_otp_sent']) && (time() - $_SESSION['last_otp_sent'] < 30);
?>
<script>
    const OTP_RECENTLY_SENT = <?php echo $recently_sent ? 'true' : 'false'; ?>;
</script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - FoodVerse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#FF6B35',
                        'primary-hover': '#E85A2A',
                        'app-bg': '#FFF8F2',
                        'card-border': '#F1EAE4',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .digit-input::-webkit-outer-spin-button,
        .digit-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body class="bg-app-bg min-h-screen flex flex-col items-center justify-center px-8 py-16">

    <div class="w-full max-w-md bg-white p-8 md:p-12 rounded-[3rem] shadow-xl border border-card-border text-center">
        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
            <i data-lucide="shield-check" class="w-10 h-10 text-primary"></i>
        </div>

        <h1 class="text-3xl font-black text-gray-900 mb-2">Security Check</h1>
        <p class="text-gray-500 text-sm mb-8">We've sent a 6-digit code to <br><strong class="text-gray-800"><?php echo htmlspecialchars($email_masked); ?></strong></p>

        <div id="alertBox" class="hidden w-full bg-red-50 text-red-600 p-4 rounded-xl mb-6 text-sm font-medium border border-red-200"></div>

        <form id="otpForm" class="space-y-8">
            <div class="flex justify-between gap-2 md:gap-3" id="otp-container">
                <input type="number" class="digit-input w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-black border-2 border-card-border rounded-xl focus:border-primary focus:outline-none transition-colors" maxlength="1" oninput="moveToNext(this, event)">
                <input type="number" class="digit-input w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-black border-2 border-card-border rounded-xl focus:border-primary focus:outline-none transition-colors" maxlength="1" oninput="moveToNext(this, event)">
                <input type="number" class="digit-input w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-black border-2 border-card-border rounded-xl focus:border-primary focus:outline-none transition-colors" maxlength="1" oninput="moveToNext(this, event)">
                <input type="number" class="digit-input w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-black border-2 border-card-border rounded-xl focus:border-primary focus:outline-none transition-colors" maxlength="1" oninput="moveToNext(this, event)">
                <input type="number" class="digit-input w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-black border-2 border-card-border rounded-xl focus:border-primary focus:outline-none transition-colors" maxlength="1" oninput="moveToNext(this, event)">
                <input type="number" class="digit-input w-12 h-14 md:w-14 md:h-16 text-center text-2xl font-black border-2 border-card-border rounded-xl focus:border-primary focus:outline-none transition-colors" maxlength="1" oninput="moveToNext(this, event)">
            </div>

            <button type="submit" id="verifyBtn" class="w-full py-5 bg-primary text-white font-black rounded-full shadow-md shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest text-sm flex items-center justify-center gap-2">
                <span>Verify Identity</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </form>

        <div class="mt-8 text-sm text-gray-500 font-medium">
            Didn't receive the code? <br>
            <button onclick="resendOTP()" id="resendBtn" class="mt-2 text-primary hover:text-primary-hover font-bold flex items-center justify-center gap-1 mx-auto transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                <span id="resendText">Resend Code in 05:00</span>
            </button>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let timeLeft = 300; // 5 minutes
        let timerInterval;

        function startTimer() {
            const resendBtn = document.getElementById('resendBtn');
            const resendText = document.getElementById('resendText');
            resendBtn.disabled = true;
            
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                timeLeft--;
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                resendText.textContent = `Resend Code in 0${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    resendBtn.disabled = false;
                    resendText.textContent = "Resend Code Now";
                }
            }, 1000);
        }

        function moveToNext(current, event) {
            if (current.value.length > 1) current.value = current.value.charAt(0);
            if (current.value.length === 1) {
                let next = current.nextElementSibling;
                if (next) next.focus();
            } else if (current.value.length === 0 && event.inputType === 'deleteContentBackward') {
                let prev = current.previousElementSibling;
                if (prev) {
                    prev.focus();
                    prev.value = '';
                }
            }
        }

        async function triggerOTP() {
            try {
                const res = await fetch('api/send_otp.php');
                const data = await res.json();
                if(!data.success) {
                    showError(data.message);
                }
            } catch(e) {
                console.error(e);
            }
        }

        async function resendOTP() {
            document.getElementById('resendBtn').disabled = true;
            document.getElementById('resendText').textContent = "Sending...";
            await triggerOTP();
            timeLeft = 300;
            startTimer();
            showSuccess("New code sent to your email!");
        }

        function showError(msg) {
            const alert = document.getElementById('alertBox');
            alert.textContent = msg;
            alert.className = "w-full bg-red-50 text-red-600 p-4 rounded-xl mb-6 text-sm font-medium border border-red-200 block";
        }
        
        function showSuccess(msg) {
            const alert = document.getElementById('alertBox');
            alert.textContent = msg;
            alert.className = "w-full bg-green-50 text-green-600 p-4 rounded-xl mb-6 text-sm font-medium border border-green-200 block";
        }

        document.getElementById('otpForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const inputs = document.querySelectorAll('.digit-input');
            let otp = '';
            inputs.forEach(input => otp += input.value);

            if (otp.length !== 6) {
                showError("Please enter all 6 digits.");
                return;
            }

            const btn = document.getElementById('verifyBtn');
            btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Verifying...`;
            lucide.createIcons();
            btn.disabled = true;

            try {
                const res = await fetch('api/verify_otp.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({otp})
                });
                const data = await res.json();

                if (data.success) {
                    if (data.flow === 'reset') {
                        window.location.href = 'reset_password.php';
                        return;
                    }
                    // Redirect based on role
                    if(data.role === 'admin') window.location.href = 'admin/dashboard.php';
                    else if(data.role === 'delivery') window.location.href = 'delivery/dashboard.php';
                    else window.location.href = 'index.php';
                } else {
                    showError(data.message);
                    btn.disabled = false;
                    btn.innerHTML = `<span>Verify Identity</span><i data-lucide="arrow-right" class="w-4 h-4"></i>`;
                    lucide.createIcons();
                }
            } catch (err) {
                showError("Something went wrong. Please try again.");
                btn.disabled = false;
                btn.innerHTML = `<span>Verify Identity</span><i data-lucide="arrow-right" class="w-4 h-4"></i>`;
                lucide.createIcons();
            }
        });

        // Initialize
        if (!OTP_RECENTLY_SENT) {
            triggerOTP();
        }
        startTimer();

        // Paste support
        document.getElementById('otp-container').addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
            if(!/^\d+$/.test(pasteData)) return; // Only allow numbers
            
            const digits = pasteData.split('').slice(0, 6);
            const inputs = document.querySelectorAll('.digit-input');
            
            digits.forEach((digit, index) => {
                if (inputs[index]) {
                    inputs[index].value = digit;
                }
            });
            
            if (digits.length === 6) {
                document.getElementById('verifyBtn').focus();
            } else if (inputs[digits.length]) {
                inputs[digits.length].focus();
            }
        });
    </script>
</body>
</html>
