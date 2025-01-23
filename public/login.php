<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

use ExpensesSystem\Auth;

$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($auth->login($_POST['email'], $_POST['password'])) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'メールアドレスまたはパスワードが正しくありません。';
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
    <title>ログイン - 家計簿アプリ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="dark-theme">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6">
                <div class="card bg-dark text-light">
                    <div class="card-body p-5">
                        <h1 class="text-center mb-4">ExpensesSystem</h1>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">メールアドレス</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">パスワード</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">ログイン</button>
                            <div class="text-center">
                                <a href="register.php" class="text-light">新規登録はこちら</a>
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
