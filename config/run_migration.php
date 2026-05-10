<?php
// config/run_migration.php
require_once __DIR__ . '/../includes/db.php';

echo "Starting Tracking Infrastructure Migration...\n";

try {
    // 1. Create Drivers Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS drivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        lat DECIMAL(10, 8) NOT NULL,
        lng DECIMAL(11, 8) NOT NULL,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");
    echo "✔ Drivers table ready.\n";

    // 2. Add columns to orders safely
    $columnsToAdd = [
        'rider_lat' => "DECIMAL(10, 8)",
        'rider_lng' => "DECIMAL(11, 8)",
        'route_geometry' => "LONGTEXT",
        'distance' => "VARCHAR(50)",
        'estimated_time' => "VARCHAR(50)"
    ];

    foreach ($columnsToAdd as $col => $type) {
        $check = $pdo->query("SHOW COLUMNS FROM orders LIKE '$col'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN $col $type");
            echo "✔ Added column $col to orders.\n";
        } else {
            echo "ℹ Column $col already exists.\n";
        }
    }

    echo "Migration Completed Successfully!\n";
} catch (PDOException $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n";
}
