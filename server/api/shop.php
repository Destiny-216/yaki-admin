<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_latest_shop(PDO $pdo): ?array {
    $stmt = $pdo->query("SELECT * FROM shop_settings ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch();
    return $row ?: null;
}

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $shop = get_latest_shop($pdo);
        if (!$shop) {
            json_response(['error' => 'shop_settings not found'], 404);
        }
        json_response($shop);
    }

    if ($method === 'PUT') {
        $shop = get_latest_shop($pdo);
        if (!$shop) {
            json_response(['error' => 'shop_settings not found'], 404);
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);

        if (!is_array($data)) {
            json_response(['error' => 'Invalid JSON body'], 400);
        }

        $allowed = ['shop_name', 'phone', 'address', 'business_hours', 'notice', 'is_open'];

        $updates = [];
        $params = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];

                if ($field === 'is_open') {
                    $value = (int)$value;
                    if (!in_array($value, [0, 1], true)) {
                        json_response(['error' => 'is_open must be 0 or 1'], 400);
                    }
                } else {
                    if ($value !== null) {
                        $value = trim((string)$value);
                    }
                }

                $updates[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
        }

        if (count($updates) === 0) {
            json_response(['error' => 'No updatable fields provided'], 400);
        }

        $params['id'] = (int)$shop['id'];

        $sql = "UPDATE shop_settings SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $newShop = get_latest_shop($pdo);
        json_response([
            'ok' => true,
            'shop' => $newShop
        ]);
    }

    json_response(['error' => 'Method not allowed'], 405);

} catch (Throwable $e) {
    json_response([
        'error' => 'server error',
        'message' => $e->getMessage()
    ], 500);
}
