<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$ordersFile = __DIR__ . '/../../onlineOrders/onlineOrders.db';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>View All Orders</title>
  <link rel='stylesheet' href='../cli/styles.css'>
</head>
<body>
  <h1>All Orders</h1>";

if (!file_exists($ordersFile)) { // finds out if the database file exists
    echo "<p>No orders found.</p>";
} else {
    $fp = fopen($ordersFile, 'rb');
    if ($fp) {
        while (!feof($fp)) {
            $lenLine = fgets($fp);
            if ($lenLine === false) break;
            $len = intval(trim($lenLine));
            if ($len <= 0) break;

            $serialized = fread($fp, $len);
            fgets($fp); // jumps to the final line

            //desserializes the order and it converts into text line so it can be read correctly
            $order = @unserialize($serialized); 
            if (is_array($order)) {
                $line = "{$order['code']} : {$order['customer']} : {$order['address']} : {$order['email']} : {$order['phone']} : {$order['totals']['total_with_vat']}€";
                echo "<div class='order-line'>" . htmlspecialchars($line) . "</div>";
            }
        }
        fclose($fp);
    } else {
        echo "<p>Error reading orders file.</p>";
    }
}

echo "
  <div class='form-actions'>
    <button onclick=\"location.href='../cli/menu.html'\">⬅ Back to Menu</button>
    <button onclick=\"location.href='../cli/index.html'\">⬅ Home</button>
  </div>
</body>
</html>";
