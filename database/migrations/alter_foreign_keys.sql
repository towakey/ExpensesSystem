-- 既存の外部キー制約を削除
PRAGMA foreign_keys=OFF;

-- transaction_details テーブルの外部キー制約を変更
CREATE TABLE new_transaction_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_id INTEGER NOT NULL,
    goods_id INTEGER,
    quantity INTEGER NOT NULL,
    price INTEGER NOT NULL,
    discount_amount INTEGER DEFAULT 0,
    goods_name TEXT,  -- 商品名を保持するカラムを追加
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (goods_id) REFERENCES goods(id) ON DELETE SET NULL
);

-- 既存のデータを新しいテーブルにコピー（商品名も含める）
INSERT INTO new_transaction_details (
    id, transaction_id, goods_id, quantity, price, discount_amount, goods_name
)
SELECT 
    td.id, td.transaction_id, td.goods_id, td.quantity, td.price, td.discount_amount,
    g.name as goods_name
FROM transaction_details td
LEFT JOIN goods g ON td.goods_id = g.id;

-- テーブルの入れ替え
DROP TABLE transaction_details;
ALTER TABLE new_transaction_details RENAME TO transaction_details;

-- transactions テーブルの外部キー制約を変更
CREATE TABLE new_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    store_id INTEGER,
    category_id INTEGER,
    date DATE NOT NULL,
    memo TEXT,
    store_name TEXT,  -- 店舗名を保持するカラムを追加
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- 既存のデータを新しいテーブルにコピー（店舗名も含める）
INSERT INTO new_transactions (
    id, user_id, store_id, category_id, date, memo, store_name
)
SELECT 
    t.id, t.user_id, t.store_id, t.category_id, t.date, t.memo,
    s.name as store_name
FROM transactions t
LEFT JOIN stores s ON t.store_id = s.id;

-- テーブルの入れ替え
DROP TABLE transactions;
ALTER TABLE new_transactions RENAME TO transactions;

PRAGMA foreign_keys=ON;
