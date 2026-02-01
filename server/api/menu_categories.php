<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

try {
  $pdo = db();

  $sql = "SELECT id, name, slug, sort_order, is_active
          FROM menu_categories
          WHERE is_active = 1
          ORDER BY sort_order ASC, id ASC";
  $stmt = $pdo->query($sql);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(["ok" => true, "data" => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error", "detail" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
