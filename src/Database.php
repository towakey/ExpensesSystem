<?php
namespace ExpensesSystem;

use PDO;
use PDOException;

class Database {
    private static ?Database $instance = null;
    private PDO $connection;
    private static bool $initialized = false;

    private function __construct() {
        $dbPath = __DIR__ . '/../database/expenses.db';
        $createTables = !file_exists($dbPath);

        try {
            $this->connection = new PDO("sqlite:$dbPath");
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            if ($createTables && !self::$initialized) {
                self::$initialized = true;
                
                // テーブルの作成
                $sql = file_get_contents(__DIR__ . '/../database/create_database.sql');
                $statements = explode(';', $sql);
                foreach ($statements as $statement) {
                    if (trim($statement) !== '') {
                        $this->connection->exec($statement);
                    }
                }

                // デフォルトユーザーの作成（開発用）
                try {
                    $defaultUser = [
                        'email' => 'test@example.com',
                        'password' => password_hash('password123', PASSWORD_DEFAULT),
                        'name' => 'テストユーザー'
                    ];
                    
                    $stmt = $this->connection->prepare(
                        "INSERT INTO users (email, password, name) VALUES (?, ?, ?)"
                    );
                    $stmt->execute([
                        $defaultUser['email'],
                        $defaultUser['password'],
                        $defaultUser['name']
                    ]);
                } catch (PDOException $e) {
                    // デフォルトユーザーの作成に失敗した場合は無視（既に存在する可能性があるため）
                    if (strpos($e->getMessage(), 'UNIQUE constraint failed') === false) {
                        throw $e;
                    }
                }
            }
        } catch (PDOException $e) {
            throw new \Exception("データベース接続エラー: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    // シリアライズを防止
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("シリアライズはサポートされていません");
    }
}
