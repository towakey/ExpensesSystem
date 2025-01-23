<?php
namespace ExpensesSystem;

class Store {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function addStore($name, $userId) {
        try {
            $stmt = $this->db->prepare("INSERT INTO stores (name, user_id) VALUES (?, ?)");
            $stmt->execute([$name, $userId]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception("店舗登録エラー: " . $e->getMessage());
        }
    }

    public function addGoods($name, $price, $storeId, $userId, $taxIncluded = true) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO goods (name, price, store_id, user_id, tax_included) 
                VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $price, $storeId, $userId, $taxIncluded]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception("商品登録エラー: " . $e->getMessage());
        }
    }

    public function getUserStores($userId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM stores WHERE user_id = ? ORDER BY name"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception("店舗一覧取得エラー: " . $e->getMessage());
        }
    }

    public function getStoreGoods($storeId, $userId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM goods 
                WHERE store_id = ? AND user_id = ?
                ORDER BY name"
            );
            $stmt->execute([$storeId, $userId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception("商品一覧取得エラー: " . $e->getMessage());
        }
    }
}
