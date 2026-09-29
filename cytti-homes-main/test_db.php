<?php
include "db_connect.php";

if ($conn) {
    echo "✅ Database Connected Successfully!";
} else {
    echo "❌ Database Connection Failed!";
}
?>
