<?php
class ConnectionManager
{
    public function connect(): PDO
    {
        $servername = 'omni-server.mysql.database.azure.com';
        $dbname     = 'omni-db';
        $username   = 'zevoevjtfj';
        $password   = 'passwordOmni1';
        $port       = 3306;

        // Detect Azure
        $isAzure = getenv('WEBSITE_SITE_NAME') !== false;

        // Common DSN
        $dsn = "mysql:host={$servername};dbname={$dbname};port={$port};charset=utf8mb4";

        // Use an absolute path to the CA that ships with THIS class file.
        // Assuming this file lives in /model/ConnectionManager.php and the cert is /model/ssl/combined-ca-certificates.pem
        $ssl_ca = __DIR__ . '/ssl/combined-ca-certificates.pem';

        // Fail fast if the file isn't where we think it is
        if (!is_file($ssl_ca)) {
            throw new RuntimeException("SSL CA bundle not found at: {$ssl_ca}");
        }

        if ($isAzure) {
            // Azure: verify server cert against CA bundle
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
            ];
        } else {
            // Local: still use TLS, but don’t hard-verify if your local CA isn't set up
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];
        }

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            // Helpful diagnostics if TLS fails
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
