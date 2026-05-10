<?php
// edit_profile.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

// Redirect riders to their profile
if (is_delivery()) {
    redirect('../delivery/profile.php');
}

$user_id = $_SESSION['user_id'];
$user = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Database error");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $profile_pic = $user['profile_pic'] ?? '';

    // Handle File Upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
        $fileName = $_FILES['profile_pic']['name'];
        $fileSize = $_FILES['profile_pic']['size'];
        $fileType = $_FILES['profile_pic']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $uploadFileDir = '../uploads/profiles/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $profile_pic = 'uploads/profiles/' . $newFileName;
            }
        }
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET phone = ?, address = ?, profile_pic = ? WHERE id = ?");
        $stmt->execute([$phone, $address, $profile_pic, $user_id]);
        $_SESSION['flash_message'] = "Profile updated successfully!";
        redirect('profile.php');
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Edit Profile - FoodVerse</title>
</head>
<body class="bg-app-bg pb-24 font-outfit">
    <style>
        @keyframes ai-rotate {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .ai-ring-container {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 150%;
            height: 150%;
            background: conic-gradient(from 0deg, #FF6B35, #6C5CE7, #00C2A8, #FF6B35);
            animation: ai-rotate 4s linear infinite;
        }
    </style>

    <header class="p-6 flex items-center justify-between bg-white/80 sticky top-0 z-50 backdrop-blur-md border-b border-card-border">
        <div class="flex items-center gap-4">
            <button onclick="history.back()" class="p-2 bg-white rounded-full hover:bg-gray-50 transition-all border border-card-border shadow-sm">
                <i data-lucide="chevron-left" class="w-6 h-6 text-gray-700"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-900">Edit Profile</h1>
        </div>
    </header>

    <main class="p-6">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-2xl text-sm">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col items-center mb-6">
                <div class="relative w-32 h-32 mb-4">
                    <!-- AI Animated Ring -->
                    <div class="absolute inset-0 rounded-full overflow-hidden shadow-lg">
                        <div class="ai-ring-container"></div>
                    </div>
                    <!-- Inner circle for mask effect -->
                    <div class="absolute inset-[3px] rounded-full bg-app-bg"></div>
                    <!-- Profile Image -->
                    <div class="absolute inset-[4px] rounded-full overflow-hidden">
                        <img src="<?php echo !empty($user['profile_pic']) ? '../' . $user['profile_pic'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'] ?? 'User') . '&background=FF6B35&color=fff'; ?>" id="preview" class="w-full h-full object-cover">
                    </div>
                </div>
                <label class="cursor-pointer bg-primary/10 border border-primary/20 text-primary px-4 py-2 rounded-xl text-xs font-bold hover:bg-primary hover:text-white transition-all">
                    Choose Picture
                    <input type="file" name="profile_pic" class="hidden" onchange="previewImage(this)">
                </label>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-widest ml-1">Phone Number</label>
                <div class="relative group">
                    <i data-lucide="phone" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                    <input type="text" name="phone" value="<?php echo sanitize($user['phone'] ?? ''); ?>" 
                        class="w-full pl-12 pr-4 py-4 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none transition-all text-gray-800">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-widest ml-1">Address</label>
                <div class="relative group">
                    <i data-lucide="map-pin" class="absolute left-4 top-4 w-5 h-5 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                    <textarea name="address" rows="3" 
                        class="w-full pl-12 pr-4 py-4 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none transition-all text-gray-800"><?php echo sanitize($user['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <button type="submit" class="w-full py-5 bg-primary text-white font-black rounded-3xl hover:bg-primary-hover hover:shadow-lg transition-all active:scale-95 shadow-md mt-6 uppercase tracking-widest">
                Save Changes
            </button>
        </form>
    </main>

    <script>
        lucide.createIcons();
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
