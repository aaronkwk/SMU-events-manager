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
            PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
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
