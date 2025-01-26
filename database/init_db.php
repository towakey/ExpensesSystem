<?php
require_once __DIR__ . '/../vendor/autoload.php';

use ExpensesSystem\Database;

// データベースファイルを削除（存在する場合）
$dbFile = __DIR__ . '/expenses.db';
if (file_exists($dbFile)) {
    unlink($dbFile);
}

// データベース接続を初期化（これによりテーブルとデフォルトデータが作成される）
$db = Database::getInstance();
echo "データベースが正常に初期化されました。\n";
