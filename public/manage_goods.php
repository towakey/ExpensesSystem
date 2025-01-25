<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use ExpensesSystem\Auth;
use ExpensesSystem\Store;

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$store = new Store();
$userId = $auth->getCurrentUserId();
$stores = $store->getUserStores($userId);

// 全店舗の商品を取得
$allGoods = [];
foreach ($stores as $storeItem) {
    $goods = $store->getStoreGoods($storeItem['id'], $userId);
    foreach ($goods as $item) {
        $item['store_name'] = $storeItem['name'];
        $allGoods[] = $item;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品管理 - 家計簿システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body.dark-theme {
            background-color: #1a1a1a;
            color: #ffffff;
        }
        .dark-theme .card {
            background-color: #2d2d2d;
        }
        .dark-theme .table {
            color: #ffffff;
        }
        .error-message {
            color: #dc3545;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="dark-theme">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>商品管理</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGoodsModal">
                <i class="bi bi-plus-lg"></i> 新規商品登録
            </button>
        </div>

        <div class="card bg-dark">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>商品名</th>
                                <th>価格</th>
                                <th>店舗</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allGoods as $goods): ?>
                            <tr>
                                <td><?= htmlspecialchars($goods['name']) ?></td>
                                <td>¥<?= number_format($goods['price']) ?></td>
                                <td><?= htmlspecialchars($goods['store_name']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm delete-goods" 
                                            data-goods-id="<?= $goods['id'] ?>"
                                            data-goods-name="<?= htmlspecialchars($goods['name']) ?>"
                                            data-store-name="<?= htmlspecialchars($goods['store_name']) ?>">
                                        <i class="bi bi-trash"></i> 削除
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 商品登録モーダル -->
    <div class="modal fade" id="addGoodsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">新規商品登録</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addGoodsForm">
                        <div class="mb-3">
                            <label for="store_id" class="form-label">店舗</label>
                            <select class="form-select bg-dark text-light" id="store_id" required>
                                <option value="">選択してください</option>
                                <?php foreach ($stores as $storeItem): ?>
                                <option value="<?= $storeItem['id'] ?>"><?= htmlspecialchars($storeItem['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="goodsName" class="form-label">商品名</label>
                            <input type="text" class="form-control bg-dark text-light" id="goodsName" required>
                        </div>
                        <div class="mb-3">
                            <label for="goodsPrice" class="form-label">価格</label>
                            <input type="number" class="form-control bg-dark text-light" id="goodsPrice" min="0" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="button" class="btn btn-primary" id="saveGoodsButton">登録</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 商品登録
            document.getElementById('saveGoodsButton').addEventListener('click', function() {
                const storeId = document.getElementById('store_id').value;
                const goodsName = document.getElementById('goodsName').value;
                const goodsPrice = document.getElementById('goodsPrice').value;

                if (!storeId || !goodsName || !goodsPrice) {
                    alert('全ての項目を入力してください。');
                    return;
                }

                fetch('api/goods.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        store_id: storeId,
                        name: goodsName,
                        price: parseInt(goodsPrice)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    location.reload();
                })
                .catch(error => {
                    alert('商品の登録に失敗しました: ' + error.message);
                });
            });

            // 商品削除
            document.querySelectorAll('.delete-goods').forEach(button => {
                button.addEventListener('click', function() {
                    const goodsId = this.dataset.goodsId;
                    const goodsName = this.dataset.goodsName;
                    const storeName = this.dataset.storeName;
                    
                    if (confirm(`${storeName}の商品「${goodsName}」を削除してもよろしいですか？\nこの操作は取り消せません。`)) {
                        fetch(`api/goods.php?id=${goodsId}`, {
                            method: 'DELETE'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                throw new Error(data.error);
                            }
                            location.reload();
                        })
                        .catch(error => {
                            alert('商品の削除に失敗しました: ' + error.message);
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
