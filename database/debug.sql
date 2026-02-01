-- 查看当前营业状态
SELECT shop_name, business_hours, is_open FROM shop_settings;

-- 手动关店测试
UPDATE shop_settings SET is_open = 0;

-- 清空测试预约
DELETE FROM reservations;
