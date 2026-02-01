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

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();
  $method = $_SERVER['REQUEST_METHOD'];

  if ($method === 'GET') {
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

    $q = '';
    if (isset($_GET['keyword'])) $q = trim((string)$_GET['keyword']);
    else if (isset($_GET['q'])) $q = trim((string)$_GET['q']);

    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';

    $where = ["mi.deleted_at IS NULL"];
    $params = [];

    if ($categoryId > 0) {
      $where[] = "mi.category_id = :category_id";
      $params[':category_id'] = $categoryId;
    }

    if ($q !== '') {
      $where[] = "mi.name LIKE :q";
      $params[':q'] = "%{$q}%";
    }

    if ($status !== '' && in_array($status, ['selling','sold_out','hidden'], true)) {
      $where[] = "mi.status = :status";
      $params[':status'] = $status;
    }

    $sql = "
      SELECT
        mi.id,
        mi.category_id,
        mc.name AS category_name,
        mi.name,
        mi.description,
        mi.price,
        mi.image_url,
        mi.status,
        mi.sort_order,
        mi.is_recommended
      FROM menu_items mi
      LEFT JOIN menu_categories mc ON mi.category_id = mc.id
      WHERE " . implode(" AND ", $where) . "
      ORDER BY mi.sort_order ASC, mi.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond(200, ["ok" => true, "data" => $rows]);
  }

  if ($method === 'POST') {
    $in = json_input();

    $category_id = (int)($in['category_id'] ?? 0);
    $name = trim((string)($in['name'] ?? ''));
    $description = isset($in['description']) ? (string)$in['description'] : null;
    $price = (int)($in['price'] ?? 0);
    $image_url = isset($in['image_url']) ? (string)$in['image_url'] : null;
    $status = (string)($in['status'] ?? 'selling');
    $sort_order = (int)($in['sort_order'] ?? 0);
    $is_recommended = (int)($in['is_recommended'] ?? 0);

    if ($category_id <= 0 || $name === '') {
      respond(400, ["ok" => false, "error" => "category_id and name are required"]);
    }

    if (!in_array($status, ['selling','sold_out','hidden'], true)) $status = 'selling';
    if ($is_recommended !== 1) $is_recommended = 0;

    $sql = "INSERT INTO menu_items
      (category_id, name, description, price, image_url, status, sort_order, is_recommended)
      VALUES
      (:category_id, :name, :description, :price, :image_url, :status, :sort_order, :is_recommended)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':category_id' => $category_id,
      ':name' => $name,
      ':description' => $description,
      ':price' => $price,
      ':image_url' => $image_url,
      ':status' => $status,
      ':sort_order' => $sort_order,
      ':is_recommended' => $is_recommended,
    ]);

    respond(200, ["ok" => true, "id" => (int)$pdo->lastInsertId()]);
  }

  if ($method === 'PUT') {
    $in = json_input();

    $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($in['id'] ?? 0);
    if ($id <= 0) {
      respond(400, ["ok" => false, "error" => "id is required"]);
    }

    $stmt0 = $pdo->prepare("SELECT * FROM menu_items WHERE id = :id AND deleted_at IS NULL");
    $stmt0->execute([':id' => $id]);
    $old = $stmt0->fetch(PDO::FETCH_ASSOC);
    if (!$old) {
      respond(404, ["ok" => false, "error" => "item not found"]);
    }

    $category_id = array_key_exists('category_id', $in) ? (int)$in['category_id'] : (int)$old['category_id'];
    $name        = array_key_exists('name', $in) ? trim((string)$in['name']) : (string)$old['name'];
    $description = array_key_exists('description', $in) ? (string)$in['description'] : (string)$old['description'];
    $price       = array_key_exists('price', $in) ? (int)$in['price'] : (int)$old['price'];
    $image_url   = array_key_exists('image_url', $in) ? (string)$in['image_url'] : (string)$old['image_url'];
    $status      = array_key_exists('status', $in) ? (string)$in['status'] : (string)$old['status'];
    $sort_order  = array_key_exists('sort_order', $in) ? (int)$in['sort_order'] : (int)$old['sort_order'];

    $is_recommended = array_key_exists('is_recommended', $in)
      ? (int)$in['is_recommended']
      : (int)$old['is_recommended'];

    if ($category_id <= 0 || $name === '') {
      respond(400, ["ok" => false, "error" => "category_id and name are required"]);
    }

    if (!in_array($status, ['selling','sold_out','hidden'], true)) $status = (string)$old['status'];
    if ($is_recommended !== 1) $is_recommended = 0;

    $sql = "UPDATE menu_items
      SET category_id = :category_id,
          name = :name,
          description = :description,
          price = :price,
          image_url = :image_url,
          status = :status,
          sort_order = :sort_order,
          is_recommended = :is_recommended
      WHERE id = :id AND deleted_at IS NULL";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':id' => $id,
      ':category_id' => $category_id,
      ':name' => $name,
      ':description' => $description,
      ':price' => $price,
      ':image_url' => $image_url,
      ':status' => $status,
      ':sort_order' => $sort_order,
      ':is_recommended' => $is_recommended,
    ]);

    respond(200, ["ok" => true]);
  }

  if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
      $in = json_input();
      $id = (int)($in['id'] ?? 0);
    }
    if ($id <= 0) {
      respond(400, ["ok" => false, "error" => "id is required"]);
    }

    $stmt = $pdo->prepare("UPDATE menu_items SET deleted_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $id]);

    respond(200, ["ok" => true]);
  }

  respond(405, ["ok" => false, "error" => "Method Not Allowed"]);

} catch (Throwable $e) {
  respond(500, [
    "ok" => false,
    "error" => "Server error",
    "detail" => $e->getMessage()
  ]);
}
