<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    $search = trim($_GET['search'] ?? '');
    $price_range = $_GET['price_range'] ?? '';
    $type = $_GET['type'] ?? '';
    $amenity = $_GET['amenity'] ?? '';
    $sort = $_GET['sort'] ?? '';

    $where = [];
    $params = [];

    // Search filter
    if (!empty($search)) {
        $where[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Price range filter
    if (!empty($price_range)) {
        if ($price_range === '500+') {
            $where[] = "price >= ?";
            $params[] = 500;
        } else {
            list($min, $max) = explode('-', $price_range);
            $where[] = "price BETWEEN ? AND ?";
            $params[] = $min;
            $params[] = $max;
        }
    }

    // Type filter
    if (!empty($type)) {
        $where[] = "type = ?";
        $params[] = $type;
    }

    // Amenity filter
    if (!empty($amenity)) {
        $where[] = "JSON_CONTAINS(amenities, ?)";
        $params[] = json_encode($amenity);
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Sorting
    $orderBy = 'ORDER BY created_at DESC';
    switch ($sort) {
        case 'price-low':
            $orderBy = 'ORDER BY price ASC';
            break;
        case 'price-high':
            $orderBy = 'ORDER BY price DESC';
            break;
        case 'rating':
            $orderBy = 'ORDER BY rating DESC';
            break;
        case 'newest':
            $orderBy = 'ORDER BY created_at DESC';
            break;
    }

    $sql = "SELECT id, title, location, price, description, amenities, type, rating, image, images, created_at FROM properties $whereClause $orderBy";

    $properties = Database::getInstance()->fetchAll($sql, $params);

    // Format properties for frontend
    foreach ($properties as &$property) {
        $property['amenities'] = json_decode($property['amenities'], true) ?? [];
        $property['images'] = json_decode($property['images'], true) ?? [];
        $property['price'] = (float) $property['price'];
        $property['rating'] = (float) $property['rating'];
    }

    echo json_encode([
        'success' => true,
        'properties' => $properties
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch properties: ' . $e->getMessage()
    ]);
}
?>
