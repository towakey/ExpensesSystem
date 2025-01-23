<?php
namespace ExpensesSystem;

class Store {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function addStore($name, $userId) {
        try {
            $this->db->beginTransaction();

            // ユーザーが存在するか確認
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                throw new \Exception("ユーザーが見つかりません");
            }

            // 店舗を登録
            $stmt = $this->db->prepare("INSERT INTO stores (name, user_id) VALUES (?, ?)");
            $stmt->execute([$name, $userId]);
            $id = $this->db->lastInsertId();

            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new \Exception("店舗登録エラー: " . $e->getMessage());
        }
    }

    public function addGoods($name, $price, $storeId, $userId) {
        try {
            $this->db->beginTransaction();

            // 店舗が存在し、かつ指定されたユーザーのものか確認
            $stmt = $this->db->prepare("SELECT id FROM stores WHERE id = ? AND user_id = ?");
            $stmt->execute([$storeId, $userId]);
            if (!$stmt->fetch()) {
                throw new \Exception("指定された店舗が見つからないか、アクセス権がありません");
            }

            // 商品を登録
            $stmt = $this->db->prepare(
                "INSERT INTO goods (name, price, store_id) VALUES (?, ?, ?)"
            );
            $stmt->execute([$name, $price, $storeId]);
            $id = $this->db->lastInsertId();

            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new \Exception("商品登録エラー: " . $e->getMessage());
        }
    }

    public function getUserStores($userId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM stores WHERE user_id = ? ORDER BY name"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception("店舗一覧取得エラー: " . $e->getMessage());
        }
    }

    public function getStoreGoods($storeId, $userId) {
        try {
            // 店舗が指定されたユーザーのものか確認
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM stores WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$storeId, $userId]);
            if ($stmt->fetchColumn() === 0) {
                throw new \Exception("指定された店舗が見つからないか、アクセス権がありません");
            }

            // 商品を取得
            $stmt = $this->db->prepare(
                "SELECT g.id, g.name, g.price 
                FROM goods g 
                WHERE g.store_id = ? 
                ORDER BY g.name"
            );
            $stmt->execute([$storeId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception("商品一覧取得エラー: " . $e->getMessage());
        }
    }
}
