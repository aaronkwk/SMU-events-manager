<?php
require_once 'ConnectionManager.php';

$cm = new ConnectionManager();
$conn = $cm->getConnection();

if ($conn) {
    echo "✅ Connection successful!";
}
?>