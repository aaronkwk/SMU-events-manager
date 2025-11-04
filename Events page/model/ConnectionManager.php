<?php

class ConnectionManager
{
    public function getConnection()
    {
        $servername = 'omni-server.mysql.database.azure.com';
        $dbname     = 'omni-db';
        $username   = 'zevoevjtfj';
        $password   = 'passwordOmni1';
        $port       = 3306;

        // Detect if running on Azure App Service
        $isAzure = getenv('WEBSITE_SITE_NAME') !== false;

        if ($isAzure) {
            // Azure App Service: CA already trusted
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ];
            $dsn = "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4;sslmode=require";
        } else {
            // Local / custom: use CA certificate
            $ssl_ca = 'C:\\ssl\\combined-ca-certificates.pem'; // adjust path for your machine
            $options = [
                PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // optional for testing
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ];
            $dsn = "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4";
        }

        try {
            $conn = new PDO($dsn, $username, $password, $options);
            return $conn;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}
?>
