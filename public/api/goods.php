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

// POSTリクエストの場合は商品を登録
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
        $store = new Store();
        $id = $store->addGoods(
            $data['name'],
            $data['price'],
            $data['store_id'],
            $_SESSION['user_id']
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

// GETリクエストの場合は商品一覧を取得
if (!isset($_GET['store_id'])) {
    http_response_code(400);
    echo json_encode(['error' => '店舗IDが必要です']);
    exit;
}

try {
    $store = new Store();
    $goods = $store->getStoreGoods($_GET['store_id'], $_SESSION['user_id']);
    
    // デバッグ情報
    error_log('Store ID: ' . $_GET['store_id']);
    error_log('User ID: ' . $_SESSION['user_id']);
    error_log('Goods: ' . print_r($goods, true));
    
    if (!is_array($goods)) {
        throw new Exception('商品データの取得に失敗しました');
    }
    
    echo json_encode(['items' => $goods]);
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
