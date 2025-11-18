<?php
function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $host = DB_HOST;
            $dbname = DB_NAME;
            $user = DB_USER;
            $pass = DB_PASS;
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $db = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    return $db;
}
