<?php
namespace ExpensesSystem;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        try {
            // データベースファイルが存在しない場合は作成
            $dbFile = $config['path'];
            $createTables = !file_exists($dbFile);
            
            $this->connection = new \PDO("sqlite:{$dbFile}");
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            
            // プラグマの設定
            $this->connection->exec('PRAGMA foreign_keys = ON');
            
            // 新規作成の場合はテーブルを作成
            if ($createTables) {
                $sql = file_get_contents(__DIR__ . '/../database/create_database.sql');
                $this->connection->exec($sql);
            }
        } catch (\PDOException $e) {
            throw new \Exception("データベース接続エラー: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
