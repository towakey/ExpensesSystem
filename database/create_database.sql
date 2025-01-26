-- データベース作成
-- SQLite3ではデータベースを作成する必要がないため、省略

-- ユーザーテーブル
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    name TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- カテゴリテーブル
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    icon TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 収入源テーブル
CREATE TABLE IF NOT EXISTS income_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 店舗テーブル
CREATE TABLE IF NOT EXISTS stores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 商品テーブル
CREATE TABLE IF NOT EXISTS goods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    store_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    price INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id)
);

-- 支出テーブル
CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    store_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    date DATE NOT NULL,
    memo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (store_id) REFERENCES stores(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- 支出明細テーブル
CREATE TABLE IF NOT EXISTS expense_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    expense_id INTEGER NOT NULL,
    goods_id INTEGER,
    goods_name TEXT,
    quantity INTEGER NOT NULL DEFAULT 1,
    price INTEGER NOT NULL,
    discount_amount INTEGER NOT NULL DEFAULT 0,
    points_used INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_id) REFERENCES expenses(id),
    FOREIGN KEY (goods_id) REFERENCES goods(id)
);

-- 収入テーブル
CREATE TABLE IF NOT EXISTS income (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    income_source_id INTEGER NOT NULL,
    amount INTEGER NOT NULL,
    date DATE NOT NULL,
    memo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (income_source_id) REFERENCES income_sources(id)
);

-- 初期データ: カテゴリ
INSERT INTO categories (name, icon) VALUES
('食費', '🍚'),
('日用品', '🧴'),
('交通費', '🚃'),
('趣味・娯楽', '🎮'),
('衣服・美容', '👕'),
('健康・医療', '💊'),
('教育・教養', '📚'),
('通信費', '📱'),
('水道・光熱費', '💡'),
('住居費', '🏠'),
('交際費', '🤝'),
('その他', '📦');

-- 初期データ: 収入源
INSERT INTO income_sources (name) VALUES
('給与'),
('ボーナス'),
('副業'),
('投資'),
('その他');
