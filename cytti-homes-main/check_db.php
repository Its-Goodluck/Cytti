<?php
require_once 'config.php';
require_once 'db_connect.php';

$db = Database::getInstance()->getPDO();

echo "Checking database 'cytti_homes' schema:\n\n";

$tables = ['users', 'properties', 'bookings', 'reviews'];

foreach ($tables as $table) {
    echo "Table: $table\n";
    $stmt = $db->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "  - {$column['Field']} {$column['Type']} " . ($column['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . " " . ($column['Key'] ? $column['Key'] : '') . " " . ($column['Default'] ? "DEFAULT '{$column['Default']}'" : '') . " " . ($column['Extra'] ? $column['Extra'] : '') . "\n";
    }
    echo "\n";
}

// Check sample data in properties
echo "Sample data in properties table:\n";
$stmt = $db->query("SELECT COUNT(*) as count FROM properties");
$count = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Number of properties: {$count['count']}\n";
?>
