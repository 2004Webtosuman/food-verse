<?php
// actions/save_location.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$province = $_POST['province'] ?? '';
$district = $_POST['district'] ?? '';
$municipality = $_POST['municipality'] ?? '';
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;

// Validation
if (empty($province) || empty($district) || empty($municipality)) {
    echo json_encode(['success' => false, 'message' => 'Please select Province, District, and Municipality.']);
    exit;
}

// Save to session
$_SESSION['user_location'] = [
    'province' => $province,
    'district' => $district,
    'municipality' => $municipality,
    'latitude' => $latitude,
    'longitude' => $longitude,
    'full_address' => "$municipality, $district, $province"
];

// If user is logged in, sync with database
if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("UPDATE users SET 
            province = ?, 
            district = ?, 
            municipality = ?, 
            latitude = ?, 
            longitude = ?,
            address = ? 
            WHERE id = ?");
        $stmt->execute([
            $province, 
            $district, 
            $municipality, 
            $latitude, 
            $longitude, 
            "$municipality, $district, $province",
            $user_id
        ]);
    } catch (PDOException $e) {
        // Log error and continue (session still works)
        error_log("Failed to sync location to DB for user $user_id: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => true, 
    'message' => 'Location saved successfully',
    'location' => $_SESSION['user_location']
]);
?>
