<?php
class ConnectionManager
{
    public function connect()
    {
        $servername = 'omni-server.mysql.database.azure.com';
        $dbname     = 'omni-db';
    // Include server in username for Azure MySQL (user@servername)
    $username   = 'zevoevjtfj@omni-server';
        $password   = 'passwordOmni1';
        $port       = 3306;

        // Detect Azure reliably
        $isAzure = getenv('WEBSITE_SITE_NAME') !== false;

        // Common DSN
        $dsn = "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4";

        if ($isAzure) {
            // === THIS IS THE FIX ===
            // 
            // 1. Define the path to the certificate *on the server*.
            //    __DIR__ gets the directory of this PHP file.
            $ssl_ca = __DIR__ . '/ssl/combined-ca-certificates.pem';

            // 2. Add the SSL_CA option to force a secure connection.
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
            ];
            
        } 
        else {
            // Local: use project-relative certificate path
            $ssl_ca = __DIR__ . '/ssl/combined-ca-certificates.pem';
            $options = [
                PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ];
        }

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}
?>