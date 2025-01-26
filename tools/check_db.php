<?php
require_once __DIR__ . '/../vendor/autoload.php';

use ExpensesSystem\Database;

function checkTableExists($db, $tableName) {
    $stmt = $db->prepare("
        SELECT name FROM sqlite_master 
        WHERE type='table' AND name = ?
    ");
    $stmt->execute([$tableName]);
    return $stmt->fetch() !== false;
}

function getTableSchema($db, $tableName) {
    $stmt = $db->prepare("
        SELECT sql FROM sqlite_master 
        WHERE type='table' AND name = ?
    ");
    $stmt->execute([$tableName]);
    return $stmt->fetch()['sql'];
}

function getTableColumns($db, $tableName) {
    $stmt = $db->prepare("PRAGMA table_info(" . $tableName . ")");
    $stmt->execute();
    return $stmt->fetchAll();
}

// データベース接続を取得
$db = Database::getInstance()->getConnection();

// チェックするテーブル
$tables = [
    'users',
    'categories',
    'income_sources',
    'stores',
    'goods',
    'expenses',
    'expense_items',
    'income'
];

echo "データベース構造チェック結果:\n\n";

foreach ($tables as $table) {
    echo "テーブル: {$table}\n";
    echo "----------------------------------------\n";
    
    if (checkTableExists($db, $table)) {
        echo "✓ テーブルは存在します\n";
        
        echo "\nカラム一覧:\n";
        $columns = getTableColumns($db, $table);
        foreach ($columns as $column) {
            echo sprintf(
                "- %s (%s) %s\n",
                $column['name'],
                $column['type'],
                $column['notnull'] ? 'NOT NULL' : 'NULL'
            );
        }
    } else {
        echo "✗ テーブルが存在しません\n";
    }
    echo "\n";
}

// 各テーブルのレコード数を表示
echo "\nテーブルのレコード数:\n";
echo "----------------------------------------\n";
foreach ($tables as $table) {
    if (checkTableExists($db, $table)) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM " . $table);
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        echo sprintf("%-20s: %d レコード\n", $table, $count);
    }
}
