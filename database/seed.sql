INSERT INTO shop_settings
(shop_name, phone, address, business_hours, notice, is_open)
VALUES
(
  '炎の味焼肉',
  '075-123-4567',
  '京都市左京区一乗寺赤ノ宮町11-2-3',
  '平日 17:00〜23:00（L.O.22:30）／土日祝 16:00〜23:00',
  '本日は通常営業しております。',
  1
);
SELECT * FROM shop_settings;

INSERT INTO reservations
(customer_name, phone, email, reservation_date, reservation_time, people, note)
VALUES
('测试用户', '080-9999-8888', 'test@example.com', '2026-02-02', '19:00:00', 3, '测试邮箱');

SELECT * FROM reservations ORDER BY id DESC LIMIT 1;
SELECT * FROM reservations ORDER BY id DESC LIMIT 5;

INSERT INTO menu_categories (name, slug, sort_order) VALUES
('厳選肉', 'premium_meat', 1),
('海鮮・野菜', 'seafood_vegetable', 2),
('ドリンク・デザート', 'drink_dessert', 3);
SELECT * FROM menu_categories  ORDER BY id DESC LIMIT 3;

INSERT INTO menu_items (category_id, name, price, image_url) VALUES
(1, '特選和牛ロース', 3800, 'img/kr1.jpg'),
(1, '特選和牛カルビ', 3500, 'img/kr2.jpg'),
(2, '海鮮盛り合わせ', 2800, 'img/haixian.jpg'),
(3, '日本酒各種', 800, 'img/noimage.jpg');
DESCRIBE menu_items;
SHOW COLUMNS FROM menu_items;
SELECT * FROM menu_items;

UPDATE menu_items
SET description = '焼肉の定番。とろける脂と肉の旨味が絶妙なバランス。'
WHERE name = '特選和牛カルビ';

TRUNCATE TABLE menu_items;
SELECT * FROM menu_items;

INSERT INTO menu_items
(category_id, name, description, price, image_url, sort_order)
VALUES
(1, '特選和牛ロース', '口の中でとろけるような、極上の和牛ロースです。', 3800, 'img/kr4.jpg', 1),
(1, '特選和牛カルビ', '焼肉の定番。とろける脂と肉の旨味が絶妙なバランス。', 3500, 'img/kr6.jpg', 2),
(1, '厚切り牛タン', 'ジューシーで歯ごたえのある、人気の厚切り牛タン。', 2500, 'img/niushe.jpg', 3),
(1, '国産豚バラ', '旨味たっぷりの国産豚バラ。香ばしく焼き上げて。', 1800, 'img/kr9.jpg', 4),
(1, '鶏もも肉', '柔らかくジューシーな鶏もも肉。お子様にも人気。', 1200, 'img/jirou.jpg', 5),
(1, '羊肉', 'ジューシーで歯ごたえのある、人気の羊肉。', 2300, 'img/yangrou.jpg', 6);

INSERT INTO menu_items
(category_id, name, description, price, image_url, sort_order)
VALUES
(2, '海鮮盛り合わせ', '新鮮な大海老、ホタテ、イカの盛り合わせ。', 2800, 'img/haixian.jpg', 1),
(2, '旬の野菜盛り合わせ', '季節ごとに変わる新鮮な野菜の盛り合わせ。', 1200, 'img/shucai.jpg', 2);

INSERT INTO menu_items
(category_id, name, description, price, image_url, sort_order)
VALUES
(3, '生ビール', '焼肉との相性抜群！キンキンに冷えた生ビール。', 600, 'img/shengpijiu.webp', 1),
(3, '日本酒各種', '全国各地の銘酒を取り揃えております。', 800, 'img/ribenjiu.webp', 2),
(3, '抹茶アイス', '食後にぴったりの、濃厚な抹茶アイス。', 500, 'img/mocha.jpg', 3);

UPDATE menu_items
SET description = '口の中でとろけるような、極上の和牛ロースです。'
WHERE name = '特選和牛ロース';

UPDATE menu_items
SET description = '焼肉の定番。とろける脂と肉の旨味が絶妙なバランス。'
WHERE name = '特選和牛カルビ';









