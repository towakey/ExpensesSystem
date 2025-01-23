<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Transaction.php';
require_once __DIR__ . '/../src/Store.php';

use ExpensesSystem\Auth;
use ExpensesSystem\Transaction;
use ExpensesSystem\Store;
use ExpensesSystem\Database;

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $auth->getCurrentUserId();
$store = new Store();
$db = Database::getInstance()->getConnection();

$message = '';
$error = '';

// カテゴリ一覧を取得
$stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// 店舗一覧を取得
$stores = $store->getUserStores($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $transaction = new Transaction();
        $result = $transaction->addExpense(
            $userId,
            $_POST['amount'],
            $_POST['date'],
            $_POST['category_id'],
            $_POST['store_id'],
            $_POST['goods_id'],
            $_POST['discount_amount'] ?? 0,
            $_POST['points_used'] ?? 0,
            $_POST['memo'] ?? null
        );
        
        if ($result) {
            $message = '支出を登録しました。';
        }
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card bg-dark text-light">
                    <div class="card-header">
                        <h4 class="mb-0">支出登録</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="post" action="" id="expenseForm">
                            <input type="hidden" name="store_id" id="store_id">
                            <input type="hidden" name="goods_id" id="goods_id">
                            <input type="hidden" name="category_id" id="category_id">

                            <div class="mb-3">
                                <label class="form-label">店舗</label>
                                <button type="button" class="btn btn-outline-light selection-button" id="storeButton" data-bs-toggle="modal" data-bs-target="#storeModal">
                                    店舗を選択してください
                                </button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">商品</label>
                                <button type="button" class="btn btn-outline-light selection-button" id="goodsButton" data-bs-toggle="modal" data-bs-target="#goodsModal" disabled>
                                    商品を選択してください
                                </button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">カテゴリ</label>
                                <button type="button" class="btn btn-outline-light selection-button" id="categoryButton" data-bs-toggle="modal" data-bs-target="#categoryModal">
                                    カテゴリを選択してください
                                </button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">金額</label>
                                <input type="number" class="form-control" name="amount" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">割引額</label>
                                <input type="number" class="form-control" name="discount_amount" value="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ポイント使用額</label>
                                <input type="number" class="form-control" name="points_used" value="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">日付</label>
                                <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">メモ</label>
                                <textarea class="form-control" name="memo"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">登録</button>
                                <a href="index.php" class="btn btn-secondary">戻る</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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
                    <div class="selection-grid" id="storeGrid">
                        <?php foreach ($stores as $s): ?>
                            <div class="selection-item" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>">
                                <i class="bi bi-shop"></i>
                                <span><?= htmlspecialchars($s['name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="selection-item" data-id="new">
                            <i class="bi bi-plus-lg"></i>
                            <span>新規店舗</span>
                        </div>
                    </div>
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
                    <div class="selection-grid" id="goodsGrid">
                        <!-- 商品は動的に読み込まれます -->
                    </div>
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
                    <div class="selection-grid" id="categoryGrid">
                        <?php foreach ($categories as $category): ?>
                            <div class="selection-item" data-id="<?= $category['id'] ?>" data-name="<?= htmlspecialchars($category['name']) ?>" data-icon="<?= $category['icon'] ?>">
                                <i><?= $category['icon'] ?></i>
                                <span><?= htmlspecialchars($category['name']) ?></span>
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
            const storeButton = document.getElementById('storeButton');
            const goodsButton = document.getElementById('goodsButton');
            const categoryButton = document.getElementById('categoryButton');
            
            const storeGrid = document.getElementById('storeGrid');
            const goodsGrid = document.getElementById('goodsGrid');
            const categoryGrid = document.getElementById('categoryGrid');

            const storeModal = new bootstrap.Modal(document.getElementById('storeModal'));
            const goodsModal = new bootstrap.Modal(document.getElementById('goodsModal'));
            const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));

            // 店舗選択
            storeGrid.addEventListener('click', async function(e) {
                const item = e.target.closest('.selection-item');
                if (!item) return;

                if (item.dataset.id === 'new') {
                    const storeName = prompt('店舗名を入力してください:');
                    if (!storeName) return;

                    try {
                        const response = await fetch('api/add_store.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ name: storeName })
                        });
                        const data = await response.json();

                        if (data.success) {
                            document.getElementById('store_id').value = data.store_id;
                            storeButton.textContent = storeName;
                            goodsButton.disabled = false;
                            storeModal.hide();
                        } else {
                            alert('店舗の登録に失敗しました: ' + data.error);
                        }
                    } catch (error) {
                        console.error('店舗の登録に失敗しました:', error);
                        alert('店舗の登録に失敗しました');
                    }
                } else {
                    document.getElementById('store_id').value = item.dataset.id;
                    storeButton.textContent = item.dataset.name;
                    goodsButton.disabled = false;
                    storeModal.hide();

                    // 商品リストを読み込む
                    try {
                        const response = await fetch(`api/get_store_goods.php?store_id=${item.dataset.id}`);
                        const goods = await response.json();
                        
                        goodsGrid.innerHTML = goods.map(g => `
                            <div class="selection-item" data-id="${g.id}" data-name="${g.name}" data-price="${g.price}">
                                <i class="bi bi-box"></i>
                                <span>${g.name} (¥${g.price.toLocaleString()})</span>
                            </div>
                        `).join('') + `
                            <div class="selection-item" data-id="new">
                                <i class="bi bi-plus-lg"></i>
                                <span>新規商品</span>
                            </div>
                        `;
                    } catch (error) {
                        console.error('商品リストの取得に失敗しました:', error);
                    }
                }
            });

            // 商品選択
            goodsGrid.addEventListener('click', async function(e) {
                const item = e.target.closest('.selection-item');
                if (!item) return;

                if (item.dataset.id === 'new') {
                    const goodsName = prompt('商品名を入力してください:');
                    if (!goodsName) return;

                    const price = prompt('価格を入力してください:');
                    if (!price || isNaN(price)) return;

                    try {
                        const response = await fetch('api/add_goods.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                store_id: document.getElementById('store_id').value,
                                name: goodsName,
                                price: parseFloat(price)
                            })
                        });
                        const data = await response.json();

                        if (data.success) {
                            document.getElementById('goods_id').value = data.goods_id;
                            goodsButton.textContent = `${goodsName} (¥${parseFloat(price).toLocaleString()})`;
                            goodsModal.hide();
                        } else {
                            alert('商品の登録に失敗しました: ' + data.error);
                        }
                    } catch (error) {
                        console.error('商品の登録に失敗しました:', error);
                        alert('商品の登録に失敗しました');
                    }
                } else {
                    document.getElementById('goods_id').value = item.dataset.id;
                    goodsButton.textContent = `${item.dataset.name} (¥${parseFloat(item.dataset.price).toLocaleString()})`;
                    goodsModal.hide();
                }
            });

            // カテゴリ選択
            categoryGrid.addEventListener('click', function(e) {
                const item = e.target.closest('.selection-item');
                if (!item) return;

                document.getElementById('category_id').value = item.dataset.id;
                categoryButton.innerHTML = `${item.dataset.icon} ${item.dataset.name}`;
                categoryModal.hide();
            });

            // フォームのバリデーション
            document.getElementById('expenseForm').addEventListener('submit', function(e) {
                const store_id = document.getElementById('store_id').value;
                const goods_id = document.getElementById('goods_id').value;
                const category_id = document.getElementById('category_id').value;

                if (!store_id || !goods_id || !category_id) {
                    e.preventDefault();
                    alert('店舗、商品、カテゴリをすべて選択してください。');
                }
            });
        });
    </script>
</body>
</html>
