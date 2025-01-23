-- データベース作成
-- SQLite3ではデータベースを作成する必要がないため、省略

-- ユーザーテーブル
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    name TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- カテゴリテーブル
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    icon TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 店舗テーブル
CREATE TABLE IF NOT EXISTS stores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    user_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 商品テーブル
CREATE TABLE IF NOT EXISTS goods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price REAL NOT NULL,
    store_id INTEGER,
    user_id INTEGER,
    tax_included INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 収入源テーブル
CREATE TABLE IF NOT EXISTS income_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 取引テーブル
CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT CHECK(type IN ('expense', 'income')) NOT NULL,
    amount REAL NOT NULL,
    date DATE NOT NULL,
    category_id INTEGER,
    store_id INTEGER,
    goods_id INTEGER,
    income_source_id INTEGER,
    discount_amount REAL DEFAULT 0,
    points_used REAL DEFAULT 0,
    memo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (store_id) REFERENCES stores(id),
    FOREIGN KEY (goods_id) REFERENCES goods(id),
    FOREIGN KEY (income_source_id) REFERENCES income_sources(id)
);

-- 初期データ: カテゴリ
INSERT INTO categories (name, icon) VALUES
('食費', '🍔'),
('日用品', '🧴'),
('交通費', '🚄'),
('娯楽費', '🎮'),
('交際費', '🍻'),
('その他', '⚙️');

-- 初期データ: 収入源
INSERT INTO income_sources (name) VALUES
('給与'),
('賞与'),
('副業'),
('投資'),
('その他');
