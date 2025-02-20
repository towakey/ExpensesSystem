<?php
// 開発環境でのエラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

use ExpensesSystem\Auth;
use ExpensesSystem\Transaction;

// SQLiteエラーをより詳細に表示
$db = ExpensesSystem\Database::getInstance()->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$auth = new Auth();

// ログインしていない場合はログインページにリダイレクト
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $auth->getCurrentUserId();
$transaction = new Transaction();

// 現在の年月を取得
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

// 取引履歴を取得
$transactions = $transaction->getMonthlyTransactions($userId, $year, $month);

// 月次サマリーを取得
$summary = $transaction->getMonthlySummary($userId, $year, $month);

// カテゴリ別サマリーを取得
$categorySummary = $transaction->getCategorySummary($userId, $year, $month);

// カテゴリデータをJavaScript用にエンコード
$categoryData = json_encode($categorySummary);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>家計簿アプリ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // カテゴリデータをグローバル変数として定義
        const categoryData = <?= $categoryData ?>;
    </script>
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
                        <a class="nav-link active" href="index.php">ホーム</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_expense.php">支出登録</a>
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
        <!-- 月選択 -->
        <div class="row mb-4">
            <div class="col">
                <div class="card bg-dark text-light">
                    <div class="card-body">
                        <form method="get" class="row g-3 align-items-center">
                            <div class="col-auto">
                                <label class="form-label">年月選択</label>
                            </div>
                            <div class="col-auto">
                                <select name="year" class="form-select">
                                    <?php for ($y = 2020; $y <= 2025; $y++): ?>
                                        <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?>年</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select name="month" class="form-select">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $m ?>月</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">表示</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- 月次サマリー -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-dark text-light h-100">
                    <div class="card-body">
                        <h5 class="card-title">収入</h5>
                        <p class="card-text fs-4">¥<?= number_format($summary['total_income']) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-light h-100">
                    <div class="card-body">
                        <h5 class="card-title">支出</h5>
                        <p class="card-text fs-4">¥<?= number_format($summary['total_expense']) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-light h-100">
                    <div class="card-body">
                        <h5 class="card-title">収支</h5>
                        <p class="card-text fs-4">¥<?= number_format($summary['total_income'] - $summary['total_expense']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- グラフと取引履歴 -->
        <div class="row">
            <!-- カテゴリ別支出グラフ -->
            <div class="col-md-6 mb-4">
                <div class="card bg-dark text-light h-100">
                    <div class="card-body">
                        <h5 class="card-title">カテゴリ別支出</h5>
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 取引履歴 -->
            <div class="col-md-6 mb-4">
                <div class="card bg-dark text-light h-100">
                    <div class="card-body">
                        <h5 class="card-title">取引履歴</h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>日付</th>
                                        <th>種類</th>
                                        <th>金額</th>
                                        <th>詳細</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <?php
                                        // 金額を計算
                                        $amount = $transaction['type'] === 'expense' 
                                            ? ($transaction['price'] * $transaction['quantity'] - $transaction['discount_amount'] - $transaction['points_used'])
                                            : $transaction['price'];
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($transaction['date']) ?></td>
                                            <td><?= $transaction['type'] === 'expense' ? '支出' : '収入' ?></td>
                                            <td class="<?= $transaction['type'] === 'expense' ? 'text-danger' : 'text-success' ?>">
                                                ¥<?= number_format($amount) ?>
                                            </td>
                                            <td>
                                                <?php if ($transaction['type'] === 'expense'): ?>
                                                    <?= htmlspecialchars($transaction['store_name']) ?> -
                                                    <?= htmlspecialchars($transaction['goods_name']) ?>
                                                    <?php if ($transaction['quantity'] > 1): ?>
                                                        (x<?= $transaction['quantity'] ?>)
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($transaction['income_source_name']) ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 新規取引登録ボタン -->
    <div class="floating-buttons">
        <a href="add_expense.php" class="btn btn-primary btn-lg rounded-circle me-2" title="支出登録">
            <i class="bi bi-dash-lg"></i>
        </a>
        <a href="add_income.php" class="btn btn-success btn-lg rounded-circle" title="収入登録">
            <i class="bi bi-plus-lg"></i>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
