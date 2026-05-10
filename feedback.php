<?php
// feedback.php - Accessible by all roles
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Security: User must be logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

// Fetch recent system feedback
$stmt = $pdo->prepare("SELECT sf.*, u.full_name, u.profile_pic FROM system_feedback sf JOIN users u ON sf.user_id = u.id ORDER BY sf.created_at DESC LIMIT 5");
$stmt->execute();
$allFeedback = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Feedback - FoodVerse</title>
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
                        secondary: '#6C5CE7',
                        accent: '#00C2A8',
                        'app-bg': '#FFF8F2',
                        'section-bg': '#FDF2EC',
                        'card-border': '#F1EAE4',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; background: #FFF8F2; color: #1A1A1A; }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-app-bg min-h-screen pb-24">

    <!-- Header -->
    <header class="p-6 flex justify-between items-center sticky top-0 bg-app-bg/80 backdrop-blur-md z-50 border-b border-card-border">
        <button onclick="history.back()" class="p-3 bg-white rounded-2xl shadow-sm border border-card-border hover:bg-gray-50 transition-all">
            <i data-lucide="chevron-left" class="w-5 h-5"></i>
        </button>
        <h1 class="text-xl font-black text-gray-900 tracking-tight uppercase italic">Platform Feedback</h1>
        <div class="w-11"></div> <!-- Spacer -->
    </header>

    <main class="px-6 py-8 max-w-lg mx-auto">
        <!-- Intro Hero -->
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-secondary/10 rounded-[32px] flex items-center justify-center mx-auto mb-6 transform rotate-3">
                <i data-lucide="heart" class="w-10 h-10 text-secondary fill-secondary/20"></i>
            </div>
            <h2 class="text-3xl font-black text-gray-900 mb-2 leading-tight uppercase italic tracking-tighter">Your feedback matters!</h2>
            <p class="text-gray-500 font-medium text-sm">Help us make FoodVerse better for everyone.</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-accent/10 border border-accent/20 text-accent p-5 rounded-[24px] mb-8 flex items-center gap-4 animate-bounce">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
                <p class="text-sm font-bold"><?php echo $success; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-primary/10 border border-primary/20 text-primary p-5 rounded-[24px] mb-8 flex items-center gap-4">
                <i data-lucide="alert-circle" class="w-6 h-6"></i>
                <p class="text-sm font-bold"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <!-- Feedback Form -->
        <section class="glass-card rounded-[40px] p-8 shadow-xl shadow-gray-200/50 mb-12">
            <form action="actions/submit_feedback.php" method="POST" class="space-y-8">
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-4">How was your experience?</label>
                    <div class="flex justify-between px-2">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <label class="cursor-pointer group relative">
                                <input type="radio" name="rating" value="<?php echo $i; ?>" required class="hidden peer">
                                <div class="w-12 h-12 flex flex-col items-center justify-center gap-1">
                                    <i data-lucide="star" class="w-8 h-8 text-gray-200 group-hover:text-primary transition-all peer-checked:text-primary peer-checked:fill-primary peer-checked:scale-125"></i>
                                    <span class="text-[10px] font-bold text-gray-300 peer-checked:text-primary"><?php echo $i; ?></span>
                                </div>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest">Anything you'd like to share?</label>
                    <textarea name="feedback_text" rows="5" required placeholder="Tell us about our service, app, or your delivery experience..."
                        class="w-full bg-white border border-card-border rounded-[24px] p-5 text-sm focus:outline-none focus:ring-8 focus:ring-primary/5 transition-all font-medium placeholder:text-gray-300"></textarea>
                </div>

                <button type="submit" class="w-full py-5 bg-gray-900 text-white font-black rounded-[24px] text-sm uppercase tracking-widest shadow-xl hover:bg-black transition-all transform active:scale-95 italic">
                    Send Feedback
                </button>
            </form>
        </section>

        <!-- Community Feedback List -->
        <section>
            <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-6 px-2 flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4 text-primary"></i>
                Recent Community Love
            </h3>
            
            <div class="space-y-4">
                <?php foreach ($allFeedback as $fb): ?>
                    <div class="bg-white border border-card-border rounded-[32px] p-6 shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-10 h-10 rounded-xl overflow-hidden border border-card-border flex-shrink-0">
                                <img src="<?php echo $fb['profile_pic'] ? $fb['profile_pic'] : 'images/default-avatar.png'; ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-gray-900"><?php echo sanitize($fb['full_name']); ?></h4>
                                <div class="flex text-primary gap-0.5">
                                    <?php for($i=0; $i<$fb['rating']; $i++) echo '<i data-lucide="star" class="w-2.5 h-2.5 fill-current"></i>'; ?>
                                </div>
                            </div>
                            <span class="text-[9px] font-bold text-gray-300 uppercase"><?php echo date('M d', strtotime($fb['created_at'])); ?></span>
                        </div>
                        <p class="text-gray-500 text-xs leading-relaxed font-medium italic">
                            "<?php echo sanitize($fb['feedback_text']); ?>"
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
