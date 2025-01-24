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

// POSTデータがある場合は入力値を保持
$formData = [
    'date' => $_POST['date'] ?? date('Y-m-d'),
    'category_id' => $_POST['category_id'] ?? '',
    'store_id' => $_POST['store_id'] ?? '',
    'memo' => $_POST['memo'] ?? '',
    'items' => $_POST['items'] ?? '[]'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // デバッグ用：POSTデータの内容を確認
        error_log('POST Data: ' . print_r($_POST, true));
        
        // 必須項目のチェック
        $requiredFields = ['items', 'date', 'category_id', 'store_id'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw new Exception(implode(', ', $missingFields) . 'は必須項目です。');
        }

        $items = json_decode($_POST['items'], true);
        if (!is_array($items) || empty($items)) {
            throw new Exception('商品が選択されていません。');
        }

        // store_idの値をログ出力
        error_log('Store ID: ' . $_POST['store_id']);

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
        // エラー内容をログに出力
        error_log('Error in add_expense.php: ' . $error);
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支出登録 - ExpensesSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .dark-theme {
            background-color: #1a1a1a;
            color: #ffffff;
        }
        .card {
            border: 1px solid #2d2d2d;
        }
        .form-control, .input-group-text {
            background-color: #2d2d2d;
            border-color: #3d3d3d;
            color: #ffffff;
        }
        .form-control:focus {
            background-color: #2d2d2d;
            border-color: #4d4d4d;
            color: #ffffff;
        }
        .error-message {
            color: #ff6b6b;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        .category-btn.selected {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: white;
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

        <form method="post" id="expenseForm">
            <input type="hidden" id="items" name="items" value='<?= htmlspecialchars($formData['items']) ?>'>
            <input type="hidden" id="category_id" name="category_id" value="<?= htmlspecialchars($formData['category_id']) ?>">
            <input type="hidden" id="store_id" name="store_id" value="<?= htmlspecialchars($formData['store_id']) ?>">

            <div class="mb-3">
                <label for="date" class="form-label">日付 <span class="text-danger">*</span></label>
                <input type="date" class="form-control bg-dark text-light" id="date" name="date" 
                       value="<?= htmlspecialchars($formData['date']) ?>" required>
                <div class="error-message" id="dateError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">カテゴリ <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($categories as $category): ?>
                    <button type="button" 
                            class="btn btn-outline-secondary category-btn" 
                            data-category-id="<?= htmlspecialchars($category['id']) ?>"
                            data-category-name="<?= htmlspecialchars($category['name']) ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <div id="selectedCategoryName" class="mt-2 text-muted small"></div>
                <div class="error-message" id="categoryError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">店舗 <span class="text-danger">*</span></label>
                <div class="row g-3">
                    <?php foreach ($stores as $store): ?>
                    <div class="col-md-3 col-sm-4 col-6">
                        <div class="card h-100 store-card bg-dark text-light" 
                             data-store-id="<?= htmlspecialchars($store['id']) ?>"
                             data-store-name="<?= htmlspecialchars($store['name']) ?>"
                             style="cursor: pointer;">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($store['name']) ?></h5>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="col-md-3 col-sm-4 col-6">
                        <div class="card h-100 bg-dark text-light" style="cursor: pointer;"
                             data-bs-toggle="modal" data-bs-target="#addStoreModal">
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <i class="bi bi-plus-lg me-2"></i>新規登録
                            </div>
                        </div>
                    </div>
                </div>
                <div id="selectedStoreName" class="mt-2 text-muted"></div>
                <div class="error-message" id="storeError"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">商品 <span class="text-danger">*</span></label>
                <button type="button" id="registerGoodsButton" class="btn btn-outline-success btn-sm mb-2" 
                        data-bs-toggle="modal" data-bs-target="#addGoodsModal" disabled>
                    新規登録
                </button>
                <div id="goodsList" class="row g-3">
                    <!-- 商品一覧がここに動的に表示されます -->
                </div>
                <div id="selectedItems"></div>
                <div class="error-message" id="itemsError"></div>
            </div>

            <div class="mb-3">
                <label for="memo" class="form-label">メモ</label>
                <textarea class="form-control bg-dark text-light" id="memo" name="memo" rows="3"><?= htmlspecialchars($formData['memo']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">登録</button>
            <a href="index.php" class="btn btn-secondary">戻る</a>
        </form>
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
                    <div id="goodsListModal" class="row row-cols-1 row-cols-md-3 g-4">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedItems = <?= $formData['items'] ?>;
            const registerGoodsButton = document.getElementById('registerGoodsButton');
            const form = document.getElementById('expenseForm');

            // 商品一覧を取得して表示する関数
            async function loadGoods(storeId) {
                try {
                    const response = await fetch(`api/goods.php?store_id=${storeId}`);
                    if (!response.ok) {
                        throw new Error('商品の取得に失敗しました');
                    }
                    const result = await response.json();
                    
                    if (result.error) {
                        throw new Error(result.error);
                    }
                    
                    const data = result.items || [];
                    const goodsList = document.getElementById('goodsList');
                    goodsList.innerHTML = ''; // 既存の商品一覧をクリア
                    
                    if (data.length === 0) {
                        goodsList.innerHTML = '<div class="col-12 text-center text-muted">商品が登録されていません</div>';
                        return;
                    }
                    
                    data.forEach(goods => {
                        const col = document.createElement('div');
                        col.className = 'col-md-3 col-sm-4 col-6';
                        
                        col.innerHTML = `
                            <div class="card h-100 goods-card bg-dark text-light" 
                                 data-goods-id="${goods.id}"
                                 data-goods-name="${goods.name}"
                                 data-goods-price="${goods.price}"
                                 style="cursor: pointer;">
                                <div class="card-body">
                                    <h5 class="card-title">${goods.name}</h5>
                                    <p class="card-text">¥${parseInt(goods.price).toLocaleString()}</p>
                                </div>
                            </div>
                        `;
                        
                        goodsList.appendChild(col);
                    });
                    
                    // 商品カードのクリックイベントを設定
                    document.querySelectorAll('.goods-card').forEach(card => {
                        card.addEventListener('click', function() {
                            const goodsId = this.dataset.goodsId;
                            const goodsName = this.dataset.goodsName;
                            const goodsPrice = parseInt(this.dataset.goodsPrice);
                            
                            // 既に選択されている商品かチェック
                            const existingItem = selectedItems.find(item => 
                                item.goods_id === goodsId && 
                                item.store_id === document.getElementById('store_id').value
                            );
                            
                            if (!existingItem) {
                                selectedItems.push({
                                    goods_id: goodsId,
                                    name: goodsName,
                                    price: goodsPrice,
                                    quantity: 1,
                                    discount_amount: 0,
                                    store_id: document.getElementById('store_id').value
                                });
                                updateSelectedItems();
                            }
                        });
                    });
                } catch (error) {
                    console.error('商品の取得に失敗しました:', error);
                    const goodsList = document.getElementById('goodsList');
                    goodsList.innerHTML = `<div class="col-12 text-center text-danger">商品の取得に失敗しました: ${error.message}</div>`;
                }
            }

            // 店舗カードの選択処理
            document.querySelectorAll('.store-card').forEach(card => {
                card.addEventListener('click', function() {
                    // 以前の選択を解除
                    document.querySelectorAll('.store-card').forEach(c => {
                        c.classList.remove('border-primary');
                    });
                    
                    // 新しい選択を適用
                    this.classList.add('border-primary');
                    const storeId = this.dataset.storeId;
                    const storeName = this.dataset.storeName;
                    
                    document.getElementById('store_id').value = storeId;
                    document.getElementById('selectedStoreName').textContent = storeName;
                    
                    // 商品登録ボタンを有効化
                    registerGoodsButton.disabled = false;
                    
                    // 商品一覧を読み込む
                    loadGoods(storeId);
                });
            });

            // 初期選択状態の設定
            const initialStoreId = '<?= htmlspecialchars($formData['store_id']) ?>';
            if (initialStoreId) {
                const initialStoreCard = document.querySelector(`.store-card[data-store-id="${initialStoreId}"]`);
                if (initialStoreCard) {
                    initialStoreCard.classList.add('border-primary');
                    document.getElementById('selectedStoreName').textContent = initialStoreCard.dataset.storeName;
                    registerGoodsButton.disabled = false;
                    loadGoods(initialStoreId);
                }
            }

            // フォーム送信前の処理
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // 一旦送信を止める
                
                // 現在の値をログ出力
                console.log('Form submission - Current values:', {
                    store_id: document.getElementById('store_id').value,
                    category_id: document.getElementById('category_id').value,
                    items: document.getElementById('items').value,
                    date: document.getElementById('date').value
                });

                if (validateForm()) {
                    // バリデーション成功時は送信
                    this.submit();
                } else {
                    window.scrollTo(0, 0);
                }
            });

            // エラーメッセージをクリア
            function clearErrors() {
                document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            }

            // フォームのバリデーション
            function validateForm() {
                let isValid = true;
                clearErrors();

                // 日付のチェック
                const date = document.getElementById('date').value;
                if (!date) {
                    document.getElementById('dateError').textContent = '日付を選択してください';
                    isValid = false;
                }

                // カテゴリのチェック
                const categoryId = document.getElementById('category_id').value;
                if (!categoryId) {
                    document.getElementById('categoryError').textContent = 'カテゴリを選択してください';
                    isValid = false;
                }

                // 店舗のチェック
                const storeId = document.getElementById('store_id').value;
                console.log('Validating store_id:', storeId); // デバッグ用
                if (!storeId) {
                    document.getElementById('storeError').textContent = '店舗を選択してください';
                    isValid = false;
                }

                // 商品のチェック
                const items = JSON.parse(document.getElementById('items').value || '[]');
                if (!items.length) {
                    document.getElementById('itemsError').textContent = '商品を選択してください';
                    isValid = false;
                }

                return isValid;
            }

            // 選択された商品を更新する関数
            function updateSelectedItems() {
                const selectedItemsContainer = document.getElementById('selectedItems');
                const itemsInput = document.getElementById('items');
                
                if (!selectedItemsContainer || !itemsInput) {
                    return;
                }
                
                // 選択された商品がない場合
                if (selectedItems.length === 0) {
                    selectedItemsContainer.innerHTML = '<div class="text-muted">商品が選択されていません</div>';
                    itemsInput.value = '[]';
                    return;
                }
                
                // 選択された商品一覧を表示
                let html = '<div class="table-responsive"><table class="table table-dark">';
                html += '<thead><tr><th>商品名</th><th>単価</th><th>数量</th><th>値引き</th><th>小計</th><th></th></tr></thead><tbody>';
                
                let total = 0;
                selectedItems.forEach((item, index) => {
                    const subtotal = (item.price * item.quantity) - (item.discount_amount || 0);
                    total += subtotal;
                    
                    html += `
                        <tr>
                            <td>${item.name}</td>
                            <td>¥${parseInt(item.price).toLocaleString()}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm bg-dark text-light" 
                                       value="${item.quantity}" min="1" 
                                       onchange="updateItemQuantity(${index}, this.value)"
                                       style="width: 80px;">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm bg-dark text-light" 
                                       value="${item.discount_amount || 0}" min="0" 
                                       onchange="updateItemDiscount(${index}, this.value)"
                                       style="width: 100px;">
                            </td>
                            <td>¥${subtotal.toLocaleString()}</td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm" 
                                        onclick="removeItem(${index})">削除</button>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                    <tr>
                        <td colspan="4" class="text-end"><strong>合計</strong></td>
                        <td colspan="2"><strong>¥${total.toLocaleString()}</strong></td>
                    </tr>
                    </tbody></table></div>`;
                
                selectedItemsContainer.innerHTML = html;
                itemsInput.value = JSON.stringify(selectedItems);
            }

            // 商品の数量を更新
            function updateItemQuantity(index, value) {
                if (selectedItems[index]) {
                    selectedItems[index].quantity = parseInt(value) || 1;
                    updateSelectedItems();
                }
            }

            // 商品の値引きを更新
            function updateItemDiscount(index, value) {
                if (selectedItems[index]) {
                    selectedItems[index].discount_amount = parseInt(value) || 0;
                    updateSelectedItems();
                }
            }

            // 商品を削除
            function removeItem(index) {
                selectedItems.splice(index, 1);
                updateSelectedItems();
            }

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
                        'Content-Type': 'application/json',
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
                    const storeList = document.querySelector('.row.g-3');
                    const newStoreCol = document.createElement('div');
                    newStoreCol.className = 'col-md-3 col-sm-4 col-6';
                    newStoreCol.innerHTML = `
                        <div class="card h-100 store-card bg-dark text-light" 
                             data-store-id="${data.id}" 
                             data-store-name="${data.name}"
                             style="cursor: pointer;">
                            <div class="card-body">
                                <h5 class="card-title">${data.name}</h5>
                            </div>
                        </div>
                    `;
                    
                    // 新規登録カードの前に挿入
                    const addNewCard = storeList.lastElementChild;
                    storeList.insertBefore(newStoreCol, addNewCard);

                    // 新しく追加した店舗カードにイベントリスナーを設定
                    const newStoreCard = newStoreCol.querySelector('.store-card');
                    newStoreCard.addEventListener('click', function() {
                        const storeId = this.dataset.storeId;
                        const storeName = this.dataset.storeName;
                        if (storeId && storeName) {
                            selectStore(storeId, storeName);
                        }
                    });

                    // モーダルを閉じる
                    bootstrap.Modal.getInstance(document.getElementById('addStoreModal')).hide();
                    document.getElementById('storeName').value = '';

                    // 保存した状態を復元
                    const formState = {
                        date: document.getElementById('date').value,
                        category_id: document.getElementById('category_id').value,
                        selectedCategory: document.getElementById('selectedCategory').textContent,
                        memo: document.getElementById('memo').value,
                        selectedItems: selectedItems
                    };

                    // 新しく追加した店舗を選択
                    selectStore(data.id, data.name);
                })
                .catch(error => {
                    alert('エラーが発生しました: ' + error.message);
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
                    loadGoods(storeId);

                    // モーダルを閉じる
                    bootstrap.Modal.getInstance(document.getElementById('addGoodsModal')).hide();
                    document.getElementById('goodsName').value = '';
                    document.getElementById('goodsPrice').value = '';
                })
                .catch(error => {
                    alert(error.message);
                });
            });

            // カテゴリボタンの選択処理
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // 以前の選択を解除
                    document.querySelectorAll('.category-btn').forEach(b => {
                        b.classList.remove('selected');
                    });
                    
                    // 新しい選択を適用
                    this.classList.add('selected');
                    const categoryId = this.dataset.categoryId;
                    const categoryName = this.dataset.categoryName;
                    
                    document.getElementById('category_id').value = categoryId;
                    document.getElementById('selectedCategoryName').textContent = `選択中: ${categoryName}`;
                });
            });

            // カテゴリの初期選択状態の設定
            const initialCategoryId = '<?= htmlspecialchars($formData['category_id']) ?>';
            if (initialCategoryId) {
                const initialCategoryBtn = document.querySelector(`.category-btn[data-category-id="${initialCategoryId}"]`);
                if (initialCategoryBtn) {
                    initialCategoryBtn.classList.add('selected');
                    document.getElementById('selectedCategoryName').textContent = `選択中: ${initialCategoryBtn.dataset.categoryName}`;
                }
            }
        });
    </script>
</body>
</html>
