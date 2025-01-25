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

// DELETE: 商品削除
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => '商品IDが必要です']);
        exit;
    }

    try {
        $store->deleteGoods($_GET['id'], $userId);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// POST: 商品登録
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || empty($data['name']) || 
        !isset($data['price']) || !is_numeric($data['price']) ||
        !isset($data['store_id']) || empty($data['store_id'])) {
        http_response_code(400);
        echo json_encode(['error' => '商品名、価格、店舗IDが必要です']);
        exit;
    }

    try {
        $id = $store->addGoods(
            $data['name'],
            $data['price'],
            $data['store_id'],
            $userId
        );
        echo json_encode([
            'id' => $id,
            'name' => $data['name'],
            'price' => $data['price']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// GET: 商品一覧取得
if (!isset($_GET['store_id'])) {
    http_response_code(400);
    echo json_encode(['error' => '店舗IDが必要です']);
    exit;
}

try {
    $goods = $store->getStoreGoods($_GET['store_id'], $userId);
    echo json_encode(['items' => $goods]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
