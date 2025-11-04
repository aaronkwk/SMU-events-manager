<?php
require_once 'ConnectionManager.php';

echo "Starting database connection test...\n";

try {
    $cm = new ConnectionManager();
    $conn = $cm->getConnection();

    if ($conn) {
        echo "✅ Connection successful!\n";

        // Check SSL version
        $stmt = $conn->query("SHOW STATUS LIKE 'Ssl_version'");
        $sslStatus = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($sslStatus['Value'])) {
            echo "🔒 SSL is active. Version: " . $sslStatus['Value'] . "\n";
        } else {
            echo "⚠️ SSL does not appear to be active.\n";
        }

        // Test query without alias
        $testStmt = $conn->query("SELECT NOW()");
        $result = $testStmt->fetch(PDO::FETCH_ASSOC);
        echo "🕒 Test query successful. Server time: " . array_values($result)[0] . "\n";
    }
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
?>
