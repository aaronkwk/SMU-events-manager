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
        $ssl_ca     = 'C:\\ssl\\combined-ca-certificates.pem'; // note the double backslashes

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // optional for testing
        ];

        try {
            $conn = new PDO(
                "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4",
                $username,
                $password,
                $options
            );
            return $conn;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}

?>
