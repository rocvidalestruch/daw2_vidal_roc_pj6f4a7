<?php
declare(strict_types=1);

// Evitem que els warnings apareguin en HTML
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Fitxer existent on es desaran les comandes
$ordersFile = __DIR__ . '/../../onlineOrders/onlineOrders.db';

// Llegim el JSON enviat pel fetch
$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Cos de la sol·licitud buit.']);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'JSON invàlid.']);
    exit;
}

// Recollim camps del payload
$code = trim($payload['code'] ?? '');
$customer = trim($payload['customer'] ?? '');
$address = trim($payload['address'] ?? '');
$email = trim($payload['email'] ?? '');
$phone = trim($payload['phone'] ?? '');
$productsData = $payload['products'] ?? [];

// Validació bàsica
if (!$code || !$customer || !$address || !$email || !$phone || count($productsData) === 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Falten camps obligatoris o productes.']);
    exit;
}

// Construïm array de productes
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

// Càlcul totals
$net = 0.0;
foreach ($products as $p) {
    $net += $p['unit_price'] * $p['quantity'];
}
$net = round($net, 2);
$vat = round($net * 0.21, 2);
$total = round($net + $vat, 2);

// Guardar comanda
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

$serialized = serialize($orderRecord);
$record = strlen($serialized)."\n".$serialized."\n";

if (file_put_contents($ordersFile, $record, FILE_APPEND | LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'No s\'ha pogut desar la comanda.']);
    exit;
}

// Retornem JSON
echo json_encode([
    'success'=>true,
    'message'=>'Comanda desada correctament.',
    'total_with_vat'=>$total
]);
