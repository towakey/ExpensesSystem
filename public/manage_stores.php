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
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>店舗管理 - 家計簿システム</title>
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
            <h1>店舗管理</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStoreModal">
                <i class="bi bi-plus-lg"></i> 新規店舗登録
            </button>
        </div>

        <div class="card bg-dark">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>店舗名</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stores as $storeItem): ?>
                            <tr>
                                <td><?= htmlspecialchars($storeItem['name']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm delete-store" 
                                            data-store-id="<?= $storeItem['id'] ?>"
                                            data-store-name="<?= htmlspecialchars($storeItem['name']) ?>">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                    body: JSON.stringify({ name: storeName })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    location.reload();
                })
                .catch(error => {
                    alert('店舗の登録に失敗しました: ' + error.message);
                });
            });

            // 店舗削除
            document.querySelectorAll('.delete-store').forEach(button => {
                button.addEventListener('click', function() {
                    const storeId = this.dataset.storeId;
                    const storeName = this.dataset.storeName;
                    
                    if (confirm(`店舗「${storeName}」を削除してもよろしいですか？\nこの操作は取り消せません。`)) {
                        fetch(`api/store.php?id=${storeId}`, {
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
                            alert('店舗の削除に失敗しました: ' + error.message);
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
