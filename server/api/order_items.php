<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

function out(array $payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') out(["ok"=>false,"error"=>"Method Not Allowed"], 405);

  $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
  if ($orderId <= 0) out(["ok"=>false,"error"=>"order_id is required"], 400);

  $stmt = $pdo->prepare("
    SELECT
      oi.id, oi.order_id, oi.menu_item_id, oi.item_name,
      oi.unit_price, oi.quantity, oi.line_total, oi.created_at
    FROM order_items oi
    WHERE oi.order_id = :order_id
    ORDER BY oi.id ASC
  ");
  $stmt->execute([':order_id' => $orderId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  out(["ok"=>true,"data"=>$rows]);

} catch (Throwable $e) {
  out(["ok" => false, "error" => "Server error", "detail" => $e->getMessage()], 500);
}
