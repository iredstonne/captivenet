<?php
namespace App\Database;

class Connection 
{
    private static ?\PDO $pdo = null;
    
    public static function getPDO(): \PDO 
    {
        if(!self::$pdo) {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=UTF8",
                DATABASE_HOST,
                DATABASE_PORT,
                DATABASE_NAME
            );
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            ];

            self::$pdo = new \PDO(
                $dsn,
                DATABASE_USERNAME,
                DATABASE_PASSWORD,
                $options
            );
        }
        return self::$pdo;
    }
}
