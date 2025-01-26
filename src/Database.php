<?php
namespace ExpensesSystem;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        try {
            $dbFile = $config['path'];
            $createTables = !file_exists($dbFile);
            
            $this->connection = new \PDO("sqlite:{$dbFile};charset=utf8");
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            
            // プラグマの設定
            $this->connection->exec('PRAGMA foreign_keys = ON');
            
            // 新規作成の場合はテーブルを作成
            if ($createTables) {
                // テーブルの作成
                $sql = file_get_contents(__DIR__ . '/../database/create_database.sql');
                $statements = explode(';', $sql);
                foreach ($statements as $statement) {
                    if (trim($statement) !== '') {
                        $this->connection->exec($statement);
                    }
                }

                // デフォルトのカテゴリを追加
                $defaultCategories = [
                    ['食費', '🍴'],
                    ['交通費', '🚃'],
                    ['日用品', '🏠'],
                    ['趣味・娯楽', '🎮'],
                    ['衣服', '👕'],
                    ['医療・健康', '🏥'],
                    ['教育', '📚'],
                    ['光熱費', '💡'],
                    ['通信費', '📱'],
                    ['その他', '📦']
                ];

                $stmt = $this->connection->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
                foreach ($defaultCategories as $category) {
                    $stmt->execute($category);
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
                } catch (\PDOException $e) {
                    // デフォルトユーザーの作成に失敗した場合は無視（既に存在する可能性があるため）
                    if (strpos($e->getMessage(), 'UNIQUE constraint failed') === false) {
                        throw $e;
                    }
                }
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

    // シリアライズを防止
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("シリアライズはサポートされていません");
    }
}
