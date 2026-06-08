<?php
header('Content-Type: application/json');

// JSON first (always works)
$prices = ["deluxe" => 250, "double" => 180, "single" => 150];
$file = __DIR__ . '/room_prices.json';
if (file_exists($file)) {
    $decoded = json_decode(file_get_contents($file), true);
    if ($decoded) $prices = $decoded;
}

// Try DB override (only if room_prices table exists)
try {
    require_once __DIR__ . '/DB.php';
    $rpDb = DB::getInstance();
    if ($rpDb !== null) {
        $stmt = $rpDb->query('SELECT room_type, price FROM room_prices');
        if ($stmt !== false) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $prices[$row['room_type']] = (float)$row['price'];
            }
        }
    }
} catch (\Throwable $e) {
    // Silently ignored — JSON values are already loaded above
}

echo json_encode($prices);
