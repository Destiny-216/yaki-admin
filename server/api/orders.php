<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

function json_input(): array {
  $raw = file_get_contents("php://input");
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function out(array $payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();
  $method = $_SERVER['REQUEST_METHOD'];

  if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id > 0) {
      $stmt = $pdo->prepare("
        SELECT
          o.id, o.table_no, o.customer_name, o.customer_phone,
          o.people_count, o.subtotal, o.tax, o.total, o.status, o.created_at
        FROM orders o
        WHERE o.id = :id
        LIMIT 1
      ");
      $stmt->execute([':id' => $id]);
      $order = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$order) out(["ok" => false, "error" => "order not found"], 404);

      $stmt2 = $pdo->prepare("
        SELECT
          oi.id, oi.order_id, oi.menu_item_id, oi.item_name,
          oi.unit_price, oi.quantity, oi.line_total, oi.created_at
        FROM order_items oi
        WHERE oi.order_id = :id
        ORDER BY oi.id ASC
      ");
      $stmt2->execute([':id' => $id]);
      $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);

      out(["ok" => true, "data" => ["order" => $order, "items" => $items]]);
    }

    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $dateFrom = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : '';
    $dateTo = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : '';

    $where = ["1=1"];
    $params = [];

    if ($status !== '' && in_array($status, ['pending','paid','done','cancelled'], true)) {
      $where[] = "o.status = :status";
      $params[':status'] = $status;
    }

    if ($q !== '') {
      $where[] = "(o.customer_name LIKE :q OR o.table_no LIKE :q OR o.customer_phone LIKE :q)";
      $params[':q'] = "%{$q}%";
    }

    if ($dateFrom !== '') {
      $where[] = "o.created_at >= :date_from";
      $params[':date_from'] = $dateFrom . " 00:00:00";
    }
    if ($dateTo !== '') {
      $where[] = "o.created_at <= :date_to";
      $params[':date_to'] = $dateTo . " 23:59:59";
    }

    $sql = "
      SELECT
        o.id, o.table_no, o.customer_name, o.customer_phone,
        o.people_count, o.subtotal, o.tax, o.total, o.status, o.created_at,
        (
          SELECT COUNT(*)
          FROM order_items oi
          WHERE oi.order_id = o.id
        ) AS items_count
      FROM orders o
      WHERE " . implode(" AND ", $where) . "
      ORDER BY o.created_at DESC, o.id DESC
      LIMIT 200
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    out(["ok" => true, "data" => $rows]);
  }

  if ($method === 'POST') {
    $in = json_input();

    $tableNo = trim((string)($in['table_no'] ?? ''));
    $customerName = trim((string)($in['customer_name'] ?? ''));
    $customerPhone = trim((string)($in['customer_phone'] ?? ''));
    $peopleCount = (int)($in['people_count'] ?? 1);
    $status = (string)($in['status'] ?? 'pending');
    $items = $in['items'] ?? [];

    if ($tableNo === '' || $customerName === '') {
      out(["ok" => false, "error" => "table_no and customer_name are required"], 400);
    }
    if ($peopleCount <= 0) $peopleCount = 1;
    if (!in_array($status, ['pending','paid','done','cancelled'], true)) $status = 'pending';
    if (!is_array($items) || count($items) === 0) {
      out(["ok" => false, "error" => "items is required"], 400);
    }

    $subtotal = 0;
    $normalizedItems = [];
    foreach ($items as $it) {
      if (!is_array($it)) continue;

      $menuItemId = isset($it['menu_item_id']) ? (int)$it['menu_item_id'] : null;
      $itemName = trim((string)($it['item_name'] ?? ''));
      $unitPrice = (int)($it['unit_price'] ?? 0);
      $quantity = (int)($it['quantity'] ?? 1);

      if ($itemName === '' || $unitPrice <= 0 || $quantity <= 0) {
        out(["ok" => false, "error" => "each item requires item_name, unit_price>0, quantity>0"], 400);
      }

      $lineTotal = $unitPrice * $quantity;
      $subtotal += $lineTotal;

      $normalizedItems[] = [
        'menu_item_id' => $menuItemId,
        'item_name' => $itemName,
        'unit_price' => $unitPrice,
        'quantity' => $quantity,
        'line_total' => $lineTotal,
      ];
    }

    $tax = 0;
    $total = $subtotal + $tax;

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
      INSERT INTO orders
      (table_no, customer_name, customer_phone, people_count, subtotal, tax, total, status, created_at)
      VALUES
      (:table_no, :customer_name, :customer_phone, :people_count, :subtotal, :tax, :total, :status, NOW())
    ");
    $stmt->execute([
      ':table_no' => $tableNo,
      ':customer_name' => $customerName,
      ':customer_phone' => $customerPhone,
      ':people_count' => $peopleCount,
      ':subtotal' => $subtotal,
      ':tax' => $tax,
      ':total' => $total,
      ':status' => $status,
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $stmt2 = $pdo->prepare("
      INSERT INTO order_items
      (order_id, menu_item_id, item_name, unit_price, quantity, line_total, created_at)
      VALUES
      (:order_id, :menu_item_id, :item_name, :unit_price, :quantity, :line_total, NOW())
    ");

    foreach ($normalizedItems as $it) {
      $stmt2->execute([
        ':order_id' => $orderId,
        ':menu_item_id' => $it['menu_item_id'],
        ':item_name' => $it['item_name'],
        ':unit_price' => $it['unit_price'],
        ':quantity' => $it['quantity'],
        ':line_total' => $it['line_total'],
      ]);
    }

    $pdo->commit();
    out(["ok" => true, "id" => $orderId]);
  }

  if ($method === 'PUT') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) out(["ok" => false, "error" => "id is required"], 400);

    $in = json_input();

    $fields = [];
    $params = [':id' => $id];

    if (isset($in['status'])) {
      $status = (string)$in['status'];
      if (!in_array($status, ['pending','paid','done','cancelled'], true)) {
        out(["ok" => false, "error" => "invalid status"], 400);
      }
      $fields[] = "status = :status";
      $params[':status'] = $status;
    }

    if (isset($in['table_no'])) {
      $fields[] = "table_no = :table_no";
      $params[':table_no'] = trim((string)$in['table_no']);
    }

    if (isset($in['people_count'])) {
      $pc = (int)$in['people_count'];
      if ($pc <= 0) $pc = 1;
      $fields[] = "people_count = :people_count";
      $params[':people_count'] = $pc;
    }

    if (isset($in['customer_name'])) {
      $fields[] = "customer_name = :customer_name";
      $params[':customer_name'] = trim((string)$in['customer_name']);
    }

    if (isset($in['customer_phone'])) {
      $fields[] = "customer_phone = :customer_phone";
      $params[':customer_phone'] = trim((string)$in['customer_phone']);
    }

    if (count($fields) === 0) out(["ok" => false, "error" => "no fields to update"], 400);

    $sql = "UPDATE orders SET " . implode(", ", $fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    out(["ok" => true]);
  }

  if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) out(["ok" => false, "error" => "id is required"], 400);

    $pdo->beginTransaction();
    $stmt1 = $pdo->prepare("DELETE FROM order_items WHERE order_id = :id");
    $stmt1->execute([':id' => $id]);
    $stmt2 = $pdo->prepare("DELETE FROM orders WHERE id = :id");
    $stmt2->execute([':id' => $id]);
    $pdo->commit();

    out(["ok" => true]);
  }

  out(["ok" => false, "error" => "Method Not Allowed"], 405);

} catch (Throwable $e) {
  out(["ok" => false, "error" => "Server error", "detail" => $e->getMessage()], 500);
}
