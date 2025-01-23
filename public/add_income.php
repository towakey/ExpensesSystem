<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Transaction.php';

use ExpensesSystem\Auth;
use ExpensesSystem\Transaction;
use ExpensesSystem\Database;

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $auth->getCurrentUserId();
$db = Database::getInstance()->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $transaction = new Transaction();
        $result = $transaction->addIncome(
            $userId,
            $_POST['amount'],
            $_POST['date'],
            $_POST['income_source_id'],
            $_POST['memo'] ?? null
        );
        
        if ($result) {
            $message = '収入を登録しました。';
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
    <title>収入登録 - 家計簿アプリ</title>
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
                        <a class="nav-link" href="add_expense.php">支出登録</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="add_income.php">収入登録</a>
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
                        <h4 class="mb-0">収入登録</h4>
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
                                <label class="form-label">収入源</label>
                                <select class="form-select" name="income_source_id" required>
                                    <option value="">選択してください</option>
                                    <?php
                                    $stmt = $db->prepare("SELECT * FROM income_sources ORDER BY name");
                                    $stmt->execute();
                                    foreach ($stmt->fetchAll() as $source):
                                    ?>
                                        <option value="<?= $source['id'] ?>"><?= htmlspecialchars($source['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">金額</label>
                                <input type="number" class="form-control" name="amount" required>
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
</body>
</html>
