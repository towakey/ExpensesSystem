<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use ExpensesSystem\Auth;
use ExpensesSystem\Store;

session_start();

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => '認証が必要です']);
    exit;
}

$store = new Store();
$userId = $auth->getCurrentUserId();

// DELETE: 店舗削除
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => '店舗IDが必要です']);
        exit;
    }

    try {
        $store->deleteStore($_GET['id'], $userId);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// POST: 店舗登録
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => '店舗名が必要です']);
        exit;
    }

    try {
        $id = $store->addStore($data['name'], $userId);
        echo json_encode([
            'id' => $id,
            'name' => $data['name']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// GET: 店舗一覧取得
try {
    $stores = $store->getUserStores($userId);
    echo json_encode($stores);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
