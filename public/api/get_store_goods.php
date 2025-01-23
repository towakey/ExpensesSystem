<?php
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Store.php';

use ExpensesSystem\Auth;
use ExpensesSystem\Store;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => '認証が必要です']);
    exit;
}

$userId = $auth->getCurrentUserId();
$store = new Store();

try {
    $goods = $store->getStoreGoods($_GET['store_id'], $userId);
    echo json_encode($goods);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
