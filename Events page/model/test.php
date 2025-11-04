<?php
echo "<pre>";
echo "===== DEBUG DATABASE CONNECTION =====\n\n";

// Show key environment info
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";

// Detect Azure environment
$azureEnvVars = ['WEBSITE_SITE_NAME', 'WEBSITE_RESOURCE_GROUP', 'WEBSITE_HOSTNAME'];
$isAzure = false;
foreach ($azureEnvVars as $var) {
    echo "$var: " . getenv($var) . "\n";
    if (!$isAzure && getenv($var) !== false) {
        $isAzure = true;
    }
}
echo "\nDetected environment: " . ($isAzure ? "Azure App Service" : "Local/Other") . "\n\n";

// Database credentials
$servername = 'omni-server.mysql.database.azure.com';
$dbname     = 'omni-db';
$username   = 'zevoevjtfj';
$password   = 'passwordOmni1';
$port       = 3306;

// Prepare PDO options
if ($isAzure) {
    echo "Using Azure SSL mode...\n";
    $dsn = "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4;sslmode=require";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
} else {
    echo "Using local SSL CA file...\n";
    $ssl_ca = 'C:\\ssl\\combined-ca-certificates.pem'; // Adjust for your local path
    $dsn = "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4";
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
}

// Try connecting
try {
    echo "Attempting PDO connection...\n";
    $conn = new PDO($dsn, $username, $password, $options);
    echo "✅ Connection successful!\n";

    // Check SSL version
    $stmt = $conn->query("SHOW STATUS LIKE 'Ssl_version'");
    $sslStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🔒 SSL Version: " . ($sslStatus['Value'] ?? 'Not Active') . "\n";

    // Test query
    $stmt2 = $conn->query("SELECT NOW()");
    $result = $stmt2->fetch(PDO::FETCH_ASSOC);
    echo "🕒 Test Query Successful. Server Time: " . array_values($result)[0] . "\n";

} catch (PDOException $e) {
    echo "❌ Database connection failed!\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "Error Message: " . $e->getMessage() . "\n";

    // Print DSN and options for debugging
    echo "\n--- Debug Info ---\n";
    echo "DSN: $dsn\n";
    echo "Options: \n";
    print_r($options);
}

echo "\n===== END DEBUG =====\n";
echo "</pre>";
?>
