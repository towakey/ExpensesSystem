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

            // 商品を取得（expense_itemsにある商品も含める）
            $stmt = $this->db->prepare(
                "SELECT DISTINCT g.id, g.name, g.price 
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

    public function deleteStore($storeId, $userId) {
        try {
            $this->db->beginTransaction();

            // 店舗が存在し、かつ指定されたユーザーのものか確認
            $stmt = $this->db->prepare("SELECT id FROM stores WHERE id = ? AND user_id = ?");
            $stmt->execute([$storeId, $userId]);
            if (!$stmt->fetch()) {
                throw new \Exception("指定された店舗が見つからないか、アクセス権がありません");
            }

            // 関連する商品を削除
            $stmt = $this->db->prepare("DELETE FROM goods WHERE store_id = ?");
            $stmt->execute([$storeId]);

            // 店舗を削除
            $stmt = $this->db->prepare("DELETE FROM stores WHERE id = ? AND user_id = ?");
            $stmt->execute([$storeId, $userId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new \Exception("店舗削除エラー: " . $e->getMessage());
        }
    }

    public function deleteGoods($goodsId, $userId) {
        try {
            $this->db->beginTransaction();

            // 商品が指定されたユーザーの店舗のものか確認
            $stmt = $this->db->prepare(
                "SELECT g.id 
                FROM goods g 
                JOIN stores s ON g.store_id = s.id 
                WHERE g.id = ? AND s.user_id = ?"
            );
            $stmt->execute([$goodsId, $userId]);
            if (!$stmt->fetch()) {
                throw new \Exception("指定された商品が見つからないか、アクセス権がありません");
            }

            // 商品を削除
            $stmt = $this->db->prepare("DELETE FROM goods WHERE id = ?");
            $stmt->execute([$goodsId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new \Exception("商品削除エラー: " . $e->getMessage());
        }
    }
}
