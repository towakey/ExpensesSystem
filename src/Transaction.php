<?php
namespace ExpensesSystem;

class Transaction {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function addExpense($userId, $amount, $date, $categoryId, $storeId, $goodsId, $discountAmount = 0, $pointsUsed = 0, $memo = null) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO transactions (user_id, type, amount, date, category_id, store_id, goods_id, discount_amount, points_used, memo) 
                VALUES (?, 'expense', ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            return $stmt->execute([$userId, $amount, $date, $categoryId, $storeId, $goodsId, $discountAmount, $pointsUsed, $memo]);
        } catch (\PDOException $e) {
            throw new \Exception("支出登録エラー: " . $e->getMessage());
        }
    }

    public function addIncome($userId, $amount, $date, $incomeSourceId, $memo = null) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO transactions (user_id, type, amount, date, income_source_id, memo) 
                VALUES (?, 'income', ?, ?, ?, ?)"
            );
            return $stmt->execute([$userId, $amount, $date, $incomeSourceId, $memo]);
        } catch (\PDOException $e) {
            throw new \Exception("収入登録エラー: " . $e->getMessage());
        }
    }

    public function getMonthlyTransactions($userId, $year, $month) {
        try {
            $startDate = sprintf("%04d-%02d-01", $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $stmt = $this->db->prepare(
                "SELECT t.*, c.name as category_name, c.icon as category_icon, 
                        s.name as store_name, g.name as goods_name,
                        i.name as income_source_name
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN stores s ON t.store_id = s.id
                LEFT JOIN goods g ON t.goods_id = g.id
                LEFT JOIN income_sources i ON t.income_source_id = i.id
                WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
                ORDER BY t.date DESC, t.created_at DESC"
            );
            $stmt->execute([$userId, $startDate, $endDate]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception("取引履歴取得エラー: " . $e->getMessage());
        }
    }

    public function getMonthlySummary($userId, $year, $month) {
        try {
            $startDate = sprintf("%04d-%02d-01", $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $stmt = $this->db->prepare(
                "SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
                    SUM(CASE WHEN type = 'expense' THEN discount_amount ELSE 0 END) as total_discount,
                    SUM(CASE WHEN type = 'expense' THEN points_used ELSE 0 END) as total_points_used
                FROM transactions
                WHERE user_id = ? AND date BETWEEN ? AND ?"
            );
            $stmt->execute([$userId, $startDate, $endDate]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            throw new \Exception("月次集計エラー: " . $e->getMessage());
        }
    }

    public function getCategorySummary($userId, $year, $month) {
        try {
            $startDate = sprintf("%04d-%02d-01", $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $stmt = $this->db->prepare(
                "SELECT 
                    c.name as category_name,
                    c.icon as category_icon,
                    SUM(t.amount) as total_amount,
                    COUNT(*) as transaction_count
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.type = 'expense' AND t.date BETWEEN ? AND ?
                GROUP BY c.id, c.name, c.icon
                ORDER BY total_amount DESC"
            );
            $stmt->execute([$userId, $startDate, $endDate]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception("カテゴリ別集計エラー: " . $e->getMessage());
        }
    }
}
