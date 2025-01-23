<?php
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Transaction.php';

use ExpensesSystem\Auth;
use ExpensesSystem\Transaction;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => '認証が必要です']);
    exit;
}

$userId = $auth->getCurrentUserId();
$transaction = new Transaction();

try {
    $result = $transaction->addIncome(
        $userId,
        $_POST['amount'],
        $_POST['date'],
        $_POST['income_source_id'],
        $_POST['memo'] ?? null
    );

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
