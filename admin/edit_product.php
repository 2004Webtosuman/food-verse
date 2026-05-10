<?php
// admin/edit_product.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    $_SESSION['flash_message'] = "Access denied.";
    redirect('../login.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;
$categories = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    $cat_stmt = $pdo->query("SELECT * FROM categories");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Error: " . $e->getMessage();
}

if (!$product) {
    $_SESSION['flash_message'] = "Product not found.";
    redirect('products.php');
}
?>
<head>
    <?php include 'includes/head.php'; ?>
    <title>Edit Product - FoodVerse</title>
</head>
<body class="bg-app-bg dark:bg-dark-bg min-h-screen text-gray-900 dark:text-gray-100 transition-colors">

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-dark-card border-r border-card-border dark:border-dark-border hidden md:flex flex-col h-full z-10 flex-shrink-0 transition-colors">
            <div class="p-6 border-b border-card-border dark:border-dark-border flex items-center h-[88px] gap-2">
                <h1 class="text-xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                <span class="bg-primary/10 text-primary text-[8px] font-bold px-2 py-0.5 rounded-full uppercase ml-1">Admin Panel</span>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 py-6 space-y-2 px-4 overflow-y-auto w-full">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="products.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 dark:bg-primary/10 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
                    <i data-lucide="package" class="w-5 h-5"></i>
                    <span>Products</span>
                </a>
                <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                    <span>Orders</span>
                </a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    <span>Users</span>
                </a>
                <a href="categories.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="tags" class="w-5 h-5"></i>
                    <span>Categories</span>
                </a>
                <a href="reports.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="bar-chart-2" class="w-5 h-5"></i>
                    <span>Reports</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-full overflow-hidden relative">
            
            <!-- Topbar -->
            <?php include 'includes/topbar.php'; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full bg-app-bg dark:bg-dark-bg pb-32 md:pb-8 transition-colors">
                <header class="flex justify-between items-center mb-8">
                    <div>
                        <a href="products.php" class="text-gray-500 hover:text-primary flex items-center gap-2 mb-2 transition-all font-medium text-sm">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Products
                        </a>
                        <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Edit Product</h2>
                    </div>
                </header>

                <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-6 md:p-8 max-w-2xl transition-colors">
                    <form action="actions/edit_product_action.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500 dark:text-gray-400 transition-colors">Product Name</label>
                                <input type="text" name="name" required value="<?php echo sanitize($product['name']); ?>"
                                    class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium transition-colors">
                            </div>
                            
                            <div class="space-y-2">
                                <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500 dark:text-gray-400 transition-colors">Category</label>
                                <select name="category_id" required
                                    class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium transition-colors">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo sanitize($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500 dark:text-gray-400 transition-colors">Price (Rs.)</label>
                                <input type="number" name="price" required step="0.01" value="<?php echo $product['price']; ?>"
                                    class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium transition-colors">
                            </div>
                            
                            <div class="space-y-2">
                                <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500 dark:text-gray-400 transition-colors">Stock Quantity</label>
                                <input type="number" name="stock" required min="0" step="1" value="<?php echo max(0, (int)$product['stock_quantity']); ?>"
                                    class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium transition-colors">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500 dark:text-gray-400 transition-colors">Veg Type</label>
                            <select name="veg_type" required
                                class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium transition-colors">
                                <option value="veg" <?php echo $product['veg_type'] == 'veg' ? 'selected' : ''; ?>>Veg</option>
                                <option value="non-veg" <?php echo $product['veg_type'] == 'non-veg' ? 'selected' : ''; ?>>Non-Veg</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500 dark:text-gray-400 transition-colors">Description</label>
                            <textarea name="description" rows="4" 
                                class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium resize-none transition-colors"><?php echo sanitize($product['description']); ?></textarea>
                        </div>

                        <div class="space-y-4">
                            <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500 dark:text-gray-400 transition-colors">Product Image</label>
                            <div class="flex items-center gap-4 bg-section-bg dark:bg-dark-bg p-4 rounded-xl border border-card-border dark:border-dark-border transition-colors">
                                <img src="../<?php echo $product['image_url']; ?>" class="w-20 h-20 rounded-xl object-cover bg-white dark:bg-dark-card border border-card-border dark:border-dark-border shadow-sm flex-shrink-0 transition-colors">
                                <div class="w-full">
                                    <input type="file" name="image" accept="image/*"
                                        class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-wider file:bg-primary/10 file:text-primary hover:file:bg-primary hover:file:text-white cursor-pointer transition-all">
                                    <p class="text-[10px] text-gray-400 mt-2 font-medium">Leave empty to keep current image</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 bg-gray-50 dark:bg-dark-bg p-4 rounded-xl border border-gray-100 dark:border-dark-border transition-colors">
                            <input type="checkbox" name="is_deal" id="is_deal" <?php echo $product['is_deal'] ? 'checked' : ''; ?>
                                class="w-4 h-4 rounded text-primary border-gray-300 focus:ring-primary">
                            <label for="is_deal" class="text-sm font-bold text-gray-900 dark:text-white cursor-pointer transition-colors">Mark as Deal of the Day</label>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full bg-primary text-white py-4 rounded-xl font-bold shadow-sm hover:bg-primary-hover active:scale-[0.98] transition-all uppercase tracking-wider text-sm">
                                Update Product
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script> lucide.createIcons(); </script>
</body>
</html>
