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
</head>
<body class="dark-theme">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">ExpensesSystem</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">ホーム</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="add_expense.php">支出登録</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_income.php">収入登録</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">ようこそ、<?= htmlspecialchars($_SESSION['user_name']) ?>さん</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">ログアウト</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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

                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">店舗</label>
                                <div class="input-group">
                                    <select class="form-select" name="store_id" id="store_id" required>
                                        <option value="">選択してください</option>
                                        <?php foreach ($store->getUserStores($userId) as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="addStoreBtn">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">商品</label>
                                <div class="input-group">
                                    <select class="form-select" name="goods_id" id="goods_id" required>
                                        <option value="">選択してください</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="addGoodsBtn">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">カテゴリ</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">選択してください</option>
                                    <?php
                                    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
                                    $stmt->execute();
                                    foreach ($stmt->fetchAll() as $category):
                                    ?>
                                        <option value="<?= $category['id'] ?>">
                                            <?= $category['icon'] ?> <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const storeSelect = document.getElementById('store_id');
            const goodsSelect = document.getElementById('goods_id');
            
            // 店舗選択時の商品リスト更新
            storeSelect.addEventListener('change', async function() {
                const storeId = this.value;
                if (!storeId) {
                    goodsSelect.innerHTML = '<option value="">選択してください</option>';
                    return;
                }

                try {
                    const response = await fetch(`api/get_store_goods.php?store_id=${storeId}`);
                    const goods = await response.json();
                    
                    goodsSelect.innerHTML = '<option value="">選択してください</option>';
                    goods.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = `${item.name} (¥${item.price.toLocaleString()})`;
                        goodsSelect.appendChild(option);
                    });
                } catch (error) {
                    console.error('商品リストの取得に失敗しました:', error);
                }
            });

            // 新規店舗登録
            document.getElementById('addStoreBtn').addEventListener('click', function() {
                const storeName = prompt('店舗名を入力してください:');
                if (!storeName) return;

                fetch('api/add_store.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name: storeName })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const option = document.createElement('option');
                        option.value = data.store_id;
                        option.textContent = storeName;
                        storeSelect.appendChild(option);
                        storeSelect.value = data.store_id;
                        storeSelect.dispatchEvent(new Event('change'));
                    } else {
                        alert('店舗の登録に失敗しました: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('店舗の登録に失敗しました:', error);
                    alert('店舗の登録に失敗しました');
                });
            });

            // 新規商品登録
            document.getElementById('addGoodsBtn').addEventListener('click', function() {
                const storeId = storeSelect.value;
                if (!storeId) {
                    alert('先に店舗を選択してください');
                    return;
                }

                const goodsName = prompt('商品名を入力してください:');
                if (!goodsName) return;

                const price = prompt('価格を入力してください:');
                if (!price || isNaN(price)) return;

                fetch('api/add_goods.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        store_id: storeId,
                        name: goodsName,
                        price: parseFloat(price)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const option = document.createElement('option');
                        option.value = data.goods_id;
                        option.textContent = `${goodsName} (¥${parseFloat(price).toLocaleString()})`;
                        goodsSelect.appendChild(option);
                        goodsSelect.value = data.goods_id;
                    } else {
                        alert('商品の登録に失敗しました: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('商品の登録に失敗しました:', error);
                    alert('商品の登録に失敗しました');
                });
            });
        });
    </script>
</body>
</html>
