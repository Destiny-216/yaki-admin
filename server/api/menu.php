<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$pdo = db();

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(["ok" => false, "error" => "Method Not Allowed"]);
  exit;
}

try {
  $status = $_GET['status'] ?? 'selling';
  $includeHidden = isset($_GET['include_hidden']) && $_GET['include_hidden'] === '1';

  $where = "";
  $params = [];

  if ($includeHidden || $status === 'all') {
    $where = "";
  } else {
    $allowed = ['selling', 'sold_out', 'hidden'];
    if (!in_array($status, $allowed, true)) {
      $status = 'selling';
    }
    $where = "WHERE mi.status = :status AND mi.deleted_at IS NULL";
    $params[':status'] = $status;
  }

  $sql = "
    SELECT
      mc.id AS category_id,
      mc.name AS category_name,
      mc.slug AS category_slug,
      mc.sort_order AS category_sort,

      mi.id,
      mi.name,
      mi.description,
      mi.price,
      mi.image_url,
      mi.status,
      mi.sort_order,
      mi.is_recommended
    FROM menu_categories mc
    LEFT JOIN menu_items mi
      ON mi.category_id = mc.id
      " . ($where ? "AND mi.deleted_at IS NULL" : "") . "
    WHERE mc.is_active = 1
    " . ($where ? "" : "") . "
    ORDER BY
      mc.sort_order ASC, mc.id ASC,
      mi.sort_order ASC, mi.id ASC
  ";

  $joinFilter = "mi.deleted_at IS NULL";
  if (!$includeHidden && $status !== 'all') {
    $joinFilter .= " AND mi.status = :status";
  }

  $sql = "
    SELECT
      mc.id AS category_id,
      mc.name AS category_name,
      mc.slug AS category_slug,
      mc.sort_order AS category_sort,

      mi.id,
      mi.name,
      mi.description,
      mi.price,
      mi.image_url,
      mi.status,
      mi.sort_order,
      mi.is_recommended
    FROM menu_categories mc
    LEFT JOIN menu_items mi
      ON mi.category_id = mc.id
      AND $joinFilter
    WHERE mc.is_active = 1
    ORDER BY
      mc.sort_order ASC, mc.id ASC,
      mi.sort_order ASC, mi.id ASC
  ";

  $stmt = $pdo->prepare($sql);
  foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
  }
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $map = [];
  foreach ($rows as $r) {
    $cid = (int)$r['category_id'];
    if (!isset($map[$cid])) {
      $map[$cid] = [
        "id" => $cid,
        "name" => $r['category_name'],
        "slug" => $r['category_slug'],
        "items" => []
      ];
    }

    if ($r['id'] !== null) {
      $map[$cid]["items"][] = [
        "id" => (int)$r['id'],
        "name" => $r['name'],
        "description" => $r['description'],
        "price" => (int)$r['price'],
        "image_url" => $r['image_url'],
        "status" => $r['status'],
        "sort_order" => (int)$r['sort_order'],
        "is_recommended" => (int)$r['is_recommended'],
      ];
    }
  }

  echo json_encode([
    "ok" => true,
    "data" => array_values($map)
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "ok" => false,
    "error" => "Server error",
    "detail" => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
