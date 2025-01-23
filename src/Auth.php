<?php
namespace ExpensesSystem;

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register($email, $password, $name) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users (email, password, name) VALUES (?, ?, ?)"
            );
            return $stmt->execute([$email, $hashedPassword, $name]);
        } catch (\PDOException $e) {
            throw new \Exception("ユーザー登録エラー: " . $e->getMessage());
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                return true;
            }
            return false;
        } catch (\PDOException $e) {
            throw new \Exception("ログインエラー: " . $e->getMessage());
        }
    }

    public function logout() {
        $_SESSION = array();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}
