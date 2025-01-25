-- 一時的に外部キー制約チェックを無効化
PRAGMA foreign_keys=OFF;

-- 既存のテーブルをバックアップ
CREATE TABLE goods_backup AS SELECT * FROM goods;

-- 既存のテーブルを削除
DROP TABLE goods;

-- 新しいテーブルを作成（外部キー制約を ON DELETE SET NULL に変更）
CREATE TABLE goods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price INTEGER NOT NULL,
    store_id INTEGER,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
);

-- データを復元
INSERT INTO goods (id, name, price, store_id)
SELECT id, name, price, store_id FROM goods_backup;

-- バックアップテーブルを削除
DROP TABLE goods_backup;

-- expense_items テーブルの外部キー制約も変更
CREATE TABLE new_expense_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    expense_id INTEGER NOT NULL,
    goods_id INTEGER,
    quantity INTEGER NOT NULL,
    price INTEGER NOT NULL,
    discount_amount INTEGER DEFAULT 0,
    points_used INTEGER DEFAULT 0,
    goods_name TEXT,
    FOREIGN KEY (expense_id) REFERENCES expenses(id),
    FOREIGN KEY (goods_id) REFERENCES goods(id) ON DELETE SET NULL
);

-- データを移行
INSERT INTO new_expense_items (
    id, expense_id, goods_id, quantity, price, discount_amount, points_used
)
SELECT id, expense_id, goods_id, quantity, price, discount_amount, points_used
FROM expense_items;

-- 商品名を更新
UPDATE new_expense_items 
SET goods_name = (
    SELECT name 
    FROM goods 
    WHERE goods.id = new_expense_items.goods_id
)
WHERE goods_id IS NOT NULL;

-- テーブルを入れ替え
DROP TABLE expense_items;
ALTER TABLE new_expense_items RENAME TO expense_items;

-- 外部キー制約チェックを再度有効化
PRAGMA foreign_keys=ON;
