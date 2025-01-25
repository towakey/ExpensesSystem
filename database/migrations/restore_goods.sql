-- 一時的に外部キー制約チェックを無効化
PRAGMA foreign_keys=OFF;

-- 既存のテーブルを削除
DROP TABLE IF EXISTS goods;

-- 新しいテーブルを作成
CREATE TABLE goods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price INTEGER NOT NULL,
    store_id INTEGER,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
);

-- サンプルデータを挿入（店舗IDは実際の値に合わせて調整してください）
INSERT INTO goods (name, price, store_id) VALUES
('牛乳', 250, 1),
('食パン', 150, 1),
('バター', 400, 1),
('シャンプー', 500, 2),
('歯磨き粉', 200, 2),
('ノート', 100, 3),
('ペン', 150, 3);

-- 外部キー制約チェックを再度有効化
PRAGMA foreign_keys=ON;
