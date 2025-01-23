<?php
namespace ExpensesSystem;

class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllCategories() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM categories ORDER BY name");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception("カテゴリの取得に失敗しました: " . $e->getMessage());
        }
    }

    public function getCategoryById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            throw new \Exception("カテゴリの取得に失敗しました: " . $e->getMessage());
        }
    }

    public function addCategory($name, $icon) {
        try {
            $stmt = $this->db->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
            $stmt->execute([$name, $icon]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception("カテゴリの追加に失敗しました: " . $e->getMessage());
        }
    }

    public function updateCategory($id, $name, $icon) {
        try {
            $stmt = $this->db->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?");
            return $stmt->execute([$name, $icon, $id]);
        } catch (\PDOException $e) {
            throw new \Exception("カテゴリの更新に失敗しました: " . $e->getMessage());
        }
    }

    public function deleteCategory($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            throw new \Exception("カテゴリの削除に失敗しました: " . $e->getMessage());
        }
    }
}
