<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$id = trim($_GET['id'] ?? '');

if (empty($id) || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    exit;
}

try {
    $sql = "SELECT id, title, location, price, description, amenities, type, rating, image, images, created_at FROM properties WHERE id = ?";
    $property = Database::getInstance()->fetch($sql, [$id]);

    if (!$property) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Property not found']);
        exit;
    }

    // Format property for frontend
    $property['amenities'] = json_decode($property['amenities'], true) ?? [];
    $property['images'] = json_decode($property['images'], true) ?? [];
    $property['price'] = (float) $property['price'];
    $property['rating'] = (float) $property['rating'];

    echo json_encode([
        'success' => true,
        'property' => $property
    ]);

} catch (Exception $e) {
    error_log("Error fetching property details: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch property details'
    ]);
}
?>
