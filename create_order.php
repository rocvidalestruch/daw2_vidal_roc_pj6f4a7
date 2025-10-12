<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$dirComandes = __DIR__ . '/onlineOrders';
$ordersFile = $dirComandes . '/onlineOrders.db'; 

if (!is_dir($dirComandes)) {
    if (!mkdir($dirComandes, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No es pot crear el directori de comandes.']);
        exit;
    }
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cos de la sol·licitud buit.']);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON invàlid.']);
    exit;
}


function validate_payload(array $p): array {
    $required = ['code', 'customer', 'address', 'email', 'phone', 'products'];
    foreach ($required as $r) {
        if (!isset($p[$r])) return [false, "Camp obligatori: $r"];
    }
    if (!is_array($p['products']) || count($p['products']) === 0) {
        return [false, "Els productes han de ser un array no buit."];
    }
    return [true, 'ok'];
}


function calculate_totals(array $products): array {
    $net = 0.0;
    foreach ($products as $item) {
        $price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.0;
        $qty = isset($item['quantity']) ? intval($item['quantity']) : 0;
        $net += $price * $qty;
    }
    $vat_amount = $net * 0.21;
    $total_with_vat = $net + $vat_amount;

    $net = round($net, 2);
    $vat_amount = round($vat_amount, 2);
    $total_with_vat = round($total_with_vat, 2);

    return [$net, $vat_amount, $total_with_vat];
}


function save_order(string $filePath, array $order): bool {
    $serialized = serialize($order);
    $record = strlen($serialized) . "\n" . $serialized . "\n";

    $fp = @fopen($filePath, 'ab');
    if ($fp === false) return false;
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    $written = fwrite($fp, $record);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $written !== false;
}

list($ok, $msg) = validate_payload($payload);
if (!$ok) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

list($net, $vat, $total_with_vat) = calculate_totals($payload['products']);

$orderRecord = [
    'code' => (string)$payload['code'],
    'customer' => (string)$payload['customer'],
    'address' => (string)$payload['address'],
    'email' => (string)$payload['email'],
    'phone' => (string)$payload['phone'],
    'products' => $payload['products'],
    'totals' => [
        'net' => $net,
        'vat' => $vat,
        'total_with_vat' => $total_with_vat
    ],
    'timestamp' => time()
];

$saved = save_order($ordersFile, $orderRecord);
if (!$saved) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No s\'ha pogut desar la comanda a disc.']);
    exit;
}

echo json_encode([
    'success' => true,
    'total_with_vat' => $total_with_vat,
    'message' => 'Comanda desada correctament.'
]);