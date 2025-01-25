<?php
namespace ExpensesSystem;

class Transaction {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function addExpense($userId, $items, $date, $categoryId, $storeId, $memo = null) {
        try {
            $this->db->beginTransaction();

            // 支出レコードを作成
            $stmt = $this->db->prepare(
                "INSERT INTO expenses (user_id, store_id, category_id, date, memo) 
                VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $storeId, $categoryId, $date, $memo]);
            $expenseId = $this->db->lastInsertId();

            // 支出明細を登録
            $stmt = $this->db->prepare(
                "INSERT INTO expense_items (expense_id, goods_id, quantity, price, discount_amount, points_used) 
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            foreach ($items as $item) {
                // 商品情報を取得（削除された商品の場合のため）
                $goodsName = null;
                if ($item['goods_id']) {
                    $stmtGoods = $this->db->prepare("SELECT name FROM goods WHERE id = ?");
                    $stmtGoods->execute([$item['goods_id']]);
                    $goods = $stmtGoods->fetch(\PDO::FETCH_ASSOC);
                    if ($goods) {
                        $goodsName = $goods['name'];
                    }
                }

                $stmt->execute([
                    $expenseId,
                    $item['goods_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['discount_amount'] ?? 0,
                    $item['points_used'] ?? 0
                ]);

                // 明細に商品名を追加
                $stmtUpdate = $this->db->prepare("UPDATE expense_items SET goods_name = ? WHERE id = ?");
                $stmtUpdate->execute([$goodsName, $this->db->lastInsertId()]);
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw new \Exception("支出の登録に失敗しました: " . $e->getMessage());
        }
    }

    public function addIncome($userId, $amount, $date, $incomeSourceId, $memo = null) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO income (user_id, income_source_id, amount, date, memo) 
                VALUES (?, ?, ?, ?, ?)"
            );
            return $stmt->execute([$userId, $incomeSourceId, $amount, $date, $memo]);
        } catch (\PDOException $e) {
            throw new \Exception("収入の登録に失敗しました: " . $e->getMessage());
        }
    }

    public function getMonthlyTransactions($userId, $year, $month) {
        try {
            // 支出を取得
            $stmt = $this->db->prepare("
                SELECT 
                    e.id,
                    e.date,
                    'expense' as type,
                    COALESCE(s.name, '削除された店舗') as store_name,
                    c.name as category_name,
                    c.icon as category_icon,
                    COALESCE(g.name, ei.goods_name, '削除された商品') as goods_name,
                    ei.price,
                    ei.quantity,
                    ei.discount_amount,
                    ei.points_used,
                    e.memo,
                    NULL as income_source_name
                FROM expenses e
                JOIN categories c ON e.category_id = c.id
                JOIN expense_items ei ON e.id = ei.expense_id
                LEFT JOIN goods g ON ei.goods_id = g.id
                LEFT JOIN stores s ON e.store_id = s.id
                WHERE e.user_id = ? 
                AND strftime('%Y', e.date) = ?
                AND strftime('%m', e.date) = ?
                UNION ALL
                SELECT 
                    i.id,
                    i.date,
                    'income' as type,
                    NULL as store_name,
                    NULL as category_name,
                    NULL as category_icon,
                    NULL as goods_name,
                    i.amount as price,
                    1 as quantity,
                    0 as discount_amount,
                    0 as points_used,
                    i.memo,
                    inc_src.name as income_source_name
                FROM income i
                JOIN income_sources inc_src ON i.income_source_id = inc_src.id
                WHERE i.user_id = ?
                AND strftime('%Y', i.date) = ?
                AND strftime('%m', i.date) = ?
                ORDER BY date DESC
            ");

            $stmt->execute([
                $userId, 
                sprintf('%04d', $year), 
                sprintf('%02d', $month),
                $userId, 
                sprintf('%04d', $year), 
                sprintf('%02d', $month)
            ]);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception("取引履歴の取得に失敗しました: " . $e->getMessage());
        }
    }

    public function getMonthlySummary($userId, $year, $month) {
        try {
            // 支出合計を取得
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(ei.price * ei.quantity - ei.discount_amount - ei.points_used), 0) as total_expense
                FROM expenses e
                JOIN expense_items ei ON e.id = ei.expense_id
                WHERE e.user_id = ?
                AND strftime('%Y', e.date) = ?
                AND strftime('%m', e.date) = ?
            ");
            $stmt->execute([$userId, sprintf('%04d', $year), sprintf('%02d', $month)]);
            $expense = $stmt->fetch();

            // 収入合計を取得
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_income
                FROM income
                WHERE user_id = ?
                AND strftime('%Y', date) = ?
                AND strftime('%m', date) = ?
            ");
            $stmt->execute([$userId, sprintf('%04d', $year), sprintf('%02d', $month)]);
            $income = $stmt->fetch();

            return [
                'total_expense' => $expense['total_expense'],
                'total_income' => $income['total_income']
            ];
        } catch (\PDOException $e) {
            throw new \Exception("月次サマリーの取得に失敗しました: " . $e->getMessage());
        }
    }

    public function getCategorySummary($userId, $year, $month) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.name as category_name,
                    c.icon as category_icon,
                    SUM(ei.price * ei.quantity - ei.discount_amount - ei.points_used) as total_amount
                FROM expenses e
                JOIN expense_items ei ON e.id = ei.expense_id
                JOIN categories c ON e.category_id = c.id
                WHERE e.user_id = ?
                AND strftime('%Y', e.date) = ?
                AND strftime('%m', e.date) = ?
                GROUP BY c.id, c.name, c.icon
                ORDER BY total_amount DESC
            ");

            $stmt->execute([$userId, sprintf('%04d', $year), sprintf('%02d', $month)]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception("カテゴリ別サマリーの取得に失敗しました: " . $e->getMessage());
        }
    }
}
