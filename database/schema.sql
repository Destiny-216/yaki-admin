CREATE DATABASE IF NOT EXISTS restaurant_system
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- 店舗情報・営業時間
CREATE TABLE shop_settings (
  id INT PRIMARY KEY AUTO_INCREMENT,

  shop_name VARCHAR(100) NOT NULL COMMENT '店舗名',
  phone VARCHAR(30) COMMENT '電話番号',
  address VARCHAR(255) COMMENT '住所',

  business_hours TEXT NOT NULL COMMENT '営業時間（表示用テキスト）',
  notice TEXT COMMENT 'お知らせ・臨時休業案内',

  is_open TINYINT(1) DEFAULT 1 COMMENT '営業状態（1:営業中 / 0:休業）',

  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- メニュー管理
CREATE TABLE menu_items (
  id INT PRIMARY KEY AUTO_INCREMENT,

  name VARCHAR(100) NOT NULL COMMENT 'メニュー名',
  description TEXT COMMENT 'メニュー説明',

  price INT NOT NULL COMMENT '価格（税込・円）',
  category VARCHAR(50) NOT NULL COMMENT 'カテゴリ（和牛・ホルモン・海鮮・ドリンク等）',

  image_url VARCHAR(255) COMMENT '商品画像URL',

  is_recommended TINYINT(1) DEFAULT 0 COMMENT 'おすすめフラグ',
  is_limited TINYINT(1) DEFAULT 0 COMMENT '期間限定フラグ',

  status ENUM('active','inactive')
    DEFAULT 'active'
    COMMENT '公開状態（active:表示 / inactive:非表示）',

  sort_order INT DEFAULT 0 COMMENT '表示順',

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 予約管理
CREATE TABLE reservations (
  id INT PRIMARY KEY AUTO_INCREMENT,

  customer_name VARCHAR(100) NOT NULL COMMENT '予約者氏名',
  phone VARCHAR(30) NOT NULL COMMENT '連絡先電話番号',

  reservation_date DATE NOT NULL COMMENT '予約日',
  reservation_time TIME NOT NULL COMMENT '予約時間',
  people INT NOT NULL COMMENT '人数',

  note TEXT COMMENT '備考',

  status ENUM('pending','confirmed','completed','cancelled')
    DEFAULT 'pending'
    COMMENT '予約ステータス',

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE reservations
ADD COLUMN email VARCHAR(150) NULL COMMENT 'メールアドレス' AFTER phone;

SELECT * FROM reservations ORDER BY id DESC LIMIT 1;






