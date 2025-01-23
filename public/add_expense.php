<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use ExpensesSystem\Auth;
use ExpensesSystem\Transaction;
use ExpensesSystem\Store;
use ExpensesSystem\Category;

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$store = new Store();
$category = new Category();
$transaction = new Transaction();

$userId = $auth->getCurrentUserId();
$stores = $store->getUserStores($userId);
$categories = $category->getAllCategories();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 必須項目のチェック
        $requiredFields = ['items', 'date', 'category_id', 'store_id'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception($field . 'は必須項目です。');
            }
        }

        $items = json_decode($_POST['items'], true);
        if (!is_array($items) || empty($items)) {
            throw new Exception('商品が選択されていません。');
        }

        $transaction->addExpense(
            $userId,
            $items,
            $_POST['date'],
            $_POST['category_id'],
            $_POST['store_id'],
            $_POST['memo'] ?? ''
        );

        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支出登録 - 家計簿アプリ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .selection-button {
            width: 100%;
            text-align: left;
            margin-bottom: 0.5rem;
            position: relative;
            padding-right: 3rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .selection-button::after {
            content: "\F4FE";
            font-family: "bootstrap-icons";
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            padding: 1rem;
        }

        .selection-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border: 1px solid #666;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .selection-item:hover {
            background-color: #444;
        }

        .selection-item.selected {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .selection-item i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body class="dark-theme">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4">支出登録</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" id="category_id" name="category_id" required>
            <input type="hidden" id="store_id" name="store_id" required>
            <input type="hidden" id="items" name="items" required>

            <div class="mb-3">
                <label for="date" class="form-label">日付</label>
                <input type="date" class="form-control bg-dark text-light" id="date" name="date" 
                       value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">カテゴリ</label>
                <div class="d-flex mb-2 align-items-center">
                    <div id="selectedCategory" class="me-2">選択してください</div>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        選択
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">店舗</label>
                <div class="d-flex mb-2 align-items-center">
                    <div id="selectedStoreName" class="me-2">選択してください</div>
                    <button type="button" class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#storeModal">
                        選択
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#addStoreModal">
                        新規登録
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">商品</label>
                <div class="d-flex mb-2">
                    <button type="button" id="addGoodsButton" class="btn btn-outline-primary btn-sm me-2" 
                            data-bs-toggle="modal" data-bs-target="#goodsModal" disabled>
                        選択
                    </button>
                    <button type="button" id="registerGoodsButton" class="btn btn-outline-success btn-sm" 
                            data-bs-toggle="modal" data-bs-target="#addGoodsModal" disabled>
                        新規登録
                    </button>
                </div>
                <div id="selectedItems"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">合計</label>
                <div class="input-group">
                    <span class="input-group-text">¥</span>
                    <input type="text" class="form-control bg-dark text-light" id="totalAmount" name="total_amount" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label for="memo" class="form-label">メモ</label>
                <textarea class="form-control bg-dark text-light" id="memo" name="memo" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">登録</button>
        </form>
    </div>

    <!-- 店舗選択モーダル -->
    <div class="modal fade" id="storeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">店舗を選択</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-md-3 g-4" id="storeList">
                        <?php foreach ($stores as $store): ?>
                        <div class="col">
                            <div class="card h-100 store-card bg-dark text-light" data-store-id="<?= htmlspecialchars($store['id']) ?>" data-store-name="<?= htmlspecialchars($store['name']) ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($store['name']) ?></h5>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 店舗登録モーダル -->
    <div class="modal fade" id="addStoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">新規店舗登録</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addStoreForm">
                        <div class="mb-3">
                            <label for="storeName" class="form-label">店舗名</label>
                            <input type="text" class="form-control bg-dark text-light" id="storeName" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="button" class="btn btn-primary" id="saveStoreButton">登録</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 商品選択モーダル -->
    <div class="modal fade" id="goodsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">商品を選択</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="goodsList" class="row row-cols-1 row-cols-md-3 g-4">
                        <!-- 商品リストがここに動的に追加されます -->
                    </div>
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
                            <label for="goodsName" class="form-label">商品名</label>
                            <input type="text" class="form-control bg-dark text-light" id="goodsName" required>
                        </div>
                        <div class="mb-3">
                            <label for="goodsPrice" class="form-label">価格</label>
                            <input type="number" class="form-control bg-dark text-light" id="goodsPrice" required>
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

    <!-- カテゴリ選択モーダル -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">カテゴリを選択</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-2 row-cols-md-3 g-3">
                        <?php foreach ($categories as $category): ?>
                            <div class="col">
                                <div class="card h-100 category-card bg-dark text-light" data-category-id="<?php echo $category['id']; ?>" data-category-name="<?php echo htmlspecialchars($category['name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <span class="category-icon"><?php echo $category['icon']; ?></span>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedItems = [];
            const addGoodsButton = document.getElementById('addGoodsButton');
            const registerGoodsButton = document.getElementById('registerGoodsButton');

            // 店舗選択時の処理
            document.querySelectorAll('.store-card').forEach(card => {
                card.addEventListener('click', function() {
                    const storeId = this.dataset.storeId;
                    const storeName = this.dataset.storeName;
                    document.getElementById('store_id').value = storeId;
                    document.getElementById('selectedStoreName').textContent = storeName;
                    
                    // 商品関連ボタンを有効化
                    addGoodsButton.disabled = false;
                    registerGoodsButton.disabled = false;
                    
                    // 店舗に紐づく商品を取得
                    loadStoreGoods(storeId);
                    
                    // モーダルを閉じる
                    bootstrap.Modal.getInstance(document.getElementById('storeModal')).hide();
                });
            });

            // 店舗登録
            document.getElementById('saveStoreButton').addEventListener('click', function() {
                const storeName = document.getElementById('storeName').value;
                if (!storeName) {
                    alert('店舗名を入力してください。');
                    return;
                }

                fetch('api/store.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: storeName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    // 店舗リストを更新
                    const storeList = document.getElementById('storeList');
                    const storeCard = document.createElement('div');
                    storeCard.className = 'col';
                    storeCard.innerHTML = `
                        <div class="card h-100 store-card bg-dark text-light" data-store-id="${data.id}" data-store-name="${data.name}">
                            <div class="card-body">
                                <h5 class="card-title">${data.name}</h5>
                            </div>
                        </div>
                    `;
                    storeList.appendChild(storeCard);

                    // 新しく追加した店舗カードにイベントリスナーを設定
                    const newStoreCard = storeCard.querySelector('.store-card');
                    newStoreCard.addEventListener('click', function() {
                        const storeId = this.dataset.storeId;
                        const storeName = this.dataset.storeName;
                        document.getElementById('store_id').value = storeId;
                        document.getElementById('selectedStoreName').textContent = storeName;
                        
                        // 商品関連ボタンを有効化
                        addGoodsButton.disabled = false;
                        registerGoodsButton.disabled = false;
                        
                        // 店舗に紐づく商品を取得
                        loadStoreGoods(storeId);
                        
                        // モーダルを閉じる
                        bootstrap.Modal.getInstance(document.getElementById('storeModal')).hide();
                    });

                    // モーダルを閉じる
                    bootstrap.Modal.getInstance(document.getElementById('addStoreModal')).hide();
                    document.getElementById('storeName').value = '';
                })
                .catch(error => {
                    alert(error.message);
                });
            });

            // 商品登録
            document.getElementById('saveGoodsButton').addEventListener('click', function() {
                const goodsName = document.getElementById('goodsName').value;
                const goodsPrice = document.getElementById('goodsPrice').value;
                const storeId = document.getElementById('store_id').value;

                if (!goodsName || !goodsPrice) {
                    alert('商品名と価格を入力してください。');
                    return;
                }

                if (!storeId) {
                    alert('先に店舗を選択してください。');
                    return;
                }

                fetch('api/goods.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        store_id: storeId,
                        name: goodsName,
                        price: parseInt(goodsPrice)
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.error) });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    // 商品リストを更新
                    loadStoreGoods(storeId);

                    // モーダルを閉じる
                    bootstrap.Modal.getInstance(document.getElementById('addGoodsModal')).hide();
                    document.getElementById('goodsName').value = '';
                    document.getElementById('goodsPrice').value = '';
                })
                .catch(error => {
                    alert(error.message);
                });
            });

            // 店舗の商品を読み込む
            function loadStoreGoods(storeId) {
                fetch(`api/goods.php?store_id=${storeId}`)
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw new Error(err.error) });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        
                        const goods = data.items || [];
                        const goodsList = document.getElementById('goodsList');
                        
                        if (goods.length === 0) {
                            goodsList.innerHTML = '<div class="col-12 text-center">商品が登録されていません</div>';
                            return;
                        }
                        
                        goodsList.innerHTML = goods.map(good => `
                            <div class="col">
                                <div class="card h-100 goods-card bg-dark text-light" 
                                     data-goods-id="${good.id}"
                                     data-goods-name="${good.name}"
                                     data-goods-price="${good.price}">
                                    <div class="card-body">
                                        <h5 class="card-title">${good.name}</h5>
                                        <p class="card-text">¥${good.price.toLocaleString()}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('');

                        // 商品選択イベントを設定
                        document.querySelectorAll('.goods-card').forEach(card => {
                            card.addEventListener('click', function() {
                                const goodsId = this.dataset.goodsId;
                                const goodsName = this.dataset.goodsName;
                                const goodsPrice = parseInt(this.dataset.goodsPrice);

                                // 商品を選択リストに追加
                                const item = {
                                    goods_id: goodsId,
                                    name: goodsName,
                                    price: goodsPrice,
                                    quantity: 1,
                                    discount_amount: 0,
                                    points_used: 0
                                };
                                selectedItems.push(item);
                                updateSelectedItems();
                                
                                // モーダルを閉じる
                                bootstrap.Modal.getInstance(document.getElementById('goodsModal')).hide();
                            });
                        });
                    })
                    .catch(error => {
                        const goodsList = document.getElementById('goodsList');
                        goodsList.innerHTML = `<div class="col-12 text-center text-danger">${error.message}</div>`;
                    });
            }

            // 選択された商品の表示を更新
            function updateSelectedItems() {
                const selectedItemsContainer = document.getElementById('selectedItems');
                let total = 0;

                selectedItemsContainer.innerHTML = selectedItems.map((item, index) => {
                    const subtotal = (item.price * item.quantity) - item.discount_amount - item.points_used;
                    total += subtotal;
                    return `
                        <div class="card mb-2 bg-dark text-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="card-title mb-0">${item.name}</h5>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(${index})">削除</button>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label">数量</label>
                                        <input type="number" class="form-control form-control-sm bg-dark text-light" 
                                               value="${item.quantity}" min="1" 
                                               onchange="updateQuantity(${index}, this.value)">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">値引き</label>
                                        <input type="number" class="form-control form-control-sm bg-dark text-light" 
                                               value="${item.discount_amount}" min="0" 
                                               onchange="updateDiscount(${index}, this.value)">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">ポイント</label>
                                        <input type="number" class="form-control form-control-sm bg-dark text-light" 
                                               value="${item.points_used}" min="0" 
                                               onchange="updatePoints(${index}, this.value)">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">小計</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">¥</span>
                                            <input type="text" class="form-control bg-dark text-light" 
                                                   value="${subtotal.toLocaleString()}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                // 合計金額を更新
                document.getElementById('totalAmount').value = total.toLocaleString();
                
                // 選択された商品を隠しフィールドに設定
                document.getElementById('items').value = JSON.stringify(selectedItems);
            }

            // 商品の数量を増やす
            window.increaseQuantity = function(index) {
                selectedItems[index].quantity++;
                updateSelectedItems();
            };

            // 商品の数量を減らす
            window.decreaseQuantity = function(index) {
                if (selectedItems[index].quantity > 1) {
                    selectedItems[index].quantity--;
                    updateSelectedItems();
                }
            };

            // 商品の数量を更新
            window.updateQuantity = function(index, value) {
                const quantity = parseInt(value);
                if (quantity > 0) {
                    selectedItems[index].quantity = quantity;
                    updateSelectedItems();
                }
            };

            // 値引き額を更新
            window.updateDiscount = function(index, value) {
                selectedItems[index].discount_amount = parseInt(value) || 0;
                updateSelectedItems();
            };

            // ポイント使用額を更新
            window.updatePoints = function(index, value) {
                selectedItems[index].points_used = parseInt(value) || 0;
                updateSelectedItems();
            };

            // 商品を削除
            window.removeItem = function(index) {
                selectedItems.splice(index, 1);
                updateSelectedItems();
            };

            // カテゴリ選択
            document.querySelectorAll('.category-card').forEach(card => {
                card.addEventListener('click', function() {
                    const categoryId = this.dataset.categoryId;
                    const categoryName = this.dataset.categoryName;
                    document.getElementById('category_id').value = categoryId;
                    document.getElementById('selectedCategory').textContent = categoryName;
                    
                    // モーダルを閉じる
                    bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
                });
            });

            // フォームの送信前にバリデーション
            document.getElementById('expenseForm').addEventListener('submit', function(e) {
                if (selectedItems.length === 0) {
                    e.preventDefault();
                    alert('商品を選択してください。');
                }
            });
        });
    </script>
</body>
</html>
