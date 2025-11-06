<?php
class ConnectionManager
{
    public function connect(): PDO
    {
        $servername = 'omni-server.mysql.database.azure.com';
        $dbname     = 'omni-db';
        $username   = 'zevoevjtfj@omni-server';            // Try 'zevoevjtfj@omni-server' if auth fails
        $password   = 'passwordOmni1';
        $port       = 3306;

        // Build an absolute path to your CA bundle (ship this file with your app)
        // If this file lives at project_root/model/ssl/combined-ca-certificates.pem
        $ssl_ca = __DIR__ . '/ssl/combined-ca-certificates.pem';

        // Sanity check (helps debug path issues quickly)
        if (!is_file($ssl_ca)) {
            throw new RuntimeException("SSL CA bundle not found at: $ssl_ca");
        }

        // Detect Azure App Service (optional)
        $isAzure = getenv('WEBSITE_SITE_NAME') !== false;

        $dsn = "mysql:host={$servername};port={$port};dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_SSL_CA       => $ssl_ca,
            // On Azure, enable strict verification. For local dev you may disable temporarily.
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => $isAzure ? true : false,
        ];

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            // Avoid echoing secrets in production
            throw new RuntimeException('Database connection failed. Check credentials, firewall, and SSL CA.');
        }
    }
}
