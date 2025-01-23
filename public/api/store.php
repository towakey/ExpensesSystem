<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use ExpensesSystem\Auth;
use ExpensesSystem\Store;

session_start();

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => '認証が必要です']);
    exit;
}

// POSTリクエストの場合は店舗を登録
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => '店舗名が必要です']);
        exit;
    }

    try {
        $store = new Store();
        $id = $store->addStore($data['name'], $_SESSION['user_id']);
        echo json_encode([
            'id' => $id,
            'name' => $data['name']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
