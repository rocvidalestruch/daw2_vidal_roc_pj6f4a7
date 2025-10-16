<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$ordersFile = __DIR__ . '/../../onlineOrders/onlineOrders.db';

$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Empty request.']);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid JSON.']);
    exit;
}

$code = trim($payload['code'] ?? '');
$customer = trim($payload['customer'] ?? '');
$address = trim($payload['address'] ?? '');
$email = trim($payload['email'] ?? '');
$phone = trim($payload['phone'] ?? '');
$productsData = $payload['products'] ?? [];


if (!$code || !$customer || !$address || !$email || !$phone || count($productsData) === 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Required fields or products are missing.']);
    exit;
}

$products = [];
foreach ($productsData as $item) {
    if (isset($item['name'], $item['unit_price'], $item['quantity'])) {
        $products[] = [
            'name' => trim($item['name']),
            'unit_price' => floatval($item['unit_price']),
            'quantity' => intval($item['quantity'])
        ];
    }
}

// Calculates the total of the order
$net = 0.0;
foreach ($products as $p) {
    $net += $p['unit_price'] * $p['quantity'];
}
$net = round($net, 2);
$vat = round($net * 0.21, 2);
$total = round($net + $vat, 2);

// Saves the order
$orderRecord = [
    'code'=>$code,
    'customer'=>$customer,
    'address'=>$address,
    'email'=>$email,
    'phone'=>$phone,
    'products'=>$products,
    'totals'=>[
        'net'=>$net,
        'vat'=>$vat,
        'total_with_vat'=>$total
    ],
    'timestamp'=>time()
];

$serialized = serialize($orderRecord); // Converts the array into a text string
$record = strlen($serialized)."\n".$serialized."\n";

//Error message if the order couldn't be saved
if (file_put_contents($ordersFile, $record, FILE_APPEND | LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Could not save order.']);
    exit;
} 

// Returns the JSON
echo json_encode([
    'success'=>true,
    'message'=>'Order saved successfully.',
    'total_with_vat'=>$total
]);
