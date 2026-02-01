<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

try {
  $pdo = db();
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "ok" => false,
    "message" => $e->getMessage()
  ]);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function readJsonBody(): array {
  $raw = file_get_contents("php://input");
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function jsonError(int $code, string $msg): void {
  http_response_code($code);
  echo json_encode(["ok" => false, "message" => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

function jsonOk(array $payload = []): void {
  echo json_encode(array_merge(["ok" => true], $payload), JSON_UNESCAPED_UNICODE);
  exit;
}

if ($method === 'GET') {
  $rows = $pdo->query("SELECT * FROM reservations ORDER BY id DESC LIMIT 200")->fetchAll();
  jsonOk(["data" => $rows]);
}

if ($method === 'POST') {
  $action = $_GET['action'] ?? '';
  if ($action === 'updateStatus') {
    $data = readJsonBody();
    if ($data === []) jsonError(400, "Invalid JSON");

    $id = (int)($data['id'] ?? 0);
    $status = trim((string)($data['status'] ?? ''));

    if ($id <= 0) jsonError(422, "Invalid id");

    $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
      jsonError(422, "Invalid status");
    }

    $stmt = $pdo->prepare("UPDATE reservations SET status = :status WHERE id = :id");
    $stmt->execute([
      ':status' => $status,
      ':id' => $id,
    ]);

    if ($stmt->rowCount() === 0) {
      $check = $pdo->prepare("SELECT id FROM reservations WHERE id = :id");
      $check->execute([':id' => $id]);
      if (!$check->fetch()) jsonError(404, "Reservation not found");
    }

    jsonOk(["id" => $id, "status" => $status]);
  }

  if ($action === 'delete') {
    $data = readJsonBody();
    if ($data === []) jsonError(400, "Invalid JSON");

    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) jsonError(422, "Invalid id");

    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
      jsonError(404, "Reservation not found");
    }

    jsonOk(["id" => $id, "deleted" => true]);
  }

  $data = readJsonBody();
  if ($data === []) jsonError(400, "Invalid JSON");

  $customer_name = trim($data['name'] ?? '');
  $phone = trim($data['phone'] ?? '');
  $email = trim($data['email'] ?? '');
  $date  = trim($data['date'] ?? '');
  $time  = trim($data['time'] ?? '');
  $people = (int)($data['guests'] ?? 0);
  $note = trim($data['message'] ?? '');

  if ($customer_name === '' || $phone === '' || $date === '' || $time === '' || $people <= 0) {
    jsonError(422, "Required fields missing");
  }

  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    jsonError(422, "Invalid date format");
  }
  if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
    jsonError(422, "Invalid time format");
  }

  if (strlen($time) === 5) $time .= ":00";

  $sql = "INSERT INTO reservations
          (customer_name, phone, email, reservation_date, reservation_time, people, note)
          VALUES
          (:customer_name, :phone, :email, :reservation_date, :reservation_time, :people, :note)";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ":customer_name" => $customer_name,
    ":phone" => $phone,
    ":email" => ($email === '' ? null : $email),
    ":reservation_date" => $date,
    ":reservation_time" => $time,
    ":people" => $people,
    ":note" => ($note === '' ? null : $note),
  ]);

  jsonOk(["id" => (int)$pdo->lastInsertId()]);
}

http_response_code(405);
echo json_encode(["ok" => false, "message" => "Method not allowed"], JSON_UNESCAPED_UNICODE);
