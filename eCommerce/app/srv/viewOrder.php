<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$code = $_POST['code'] ?? '';
$code = trim($code);

$ordersFile = __DIR__ . '/../../onlineOrders/onlineOrders.db';

$fp = fopen($ordersFile, 'rb');
if (!$fp) {
    echo json_encode(['success' => false, 'message' => 'Error reading orders file.']);
    exit;
}

$found = null;
while (!feof($fp)) {
    $lenLine = fgets($fp);
    if ($lenLine === false) break;
    $len = intval(trim($lenLine));
    if ($len <= 0) break;

    $serialized = fread($fp, $len);
    fgets($fp);
    $order = @unserialize($serialized);

    if (is_array($order) && $order['code'] === $code) {
        $found = $order;
        break;
    }
}
fclose($fp);

if ($found) {
    echo json_encode(['success' => true, 'order' => $found]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
}
