<?php
ob_start();
session_start();

require '../DB.php';
require '../Util.php';

if (isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] == 'true') {
    if (isset($_SESSION["isAdmin"]) && $_SESSION["isAdmin"][1] == "true") {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $deluxe = isset($_POST["deluxe"]) ? intval($_POST["deluxe"]) : 0;
            $double = isset($_POST["double"]) ? intval($_POST["double"]) : 0;
            $single = isset($_POST["single"]) ? intval($_POST["single"]) : 0;

            if ($deluxe <= 0 || $double <= 0 || $single <= 0) {
                echo Util::displayAlertV1("Prices must be greater than zero.", "danger");
            } else {
                $errors = [];

                // Save to JSON (primary — always works without migration)
                $prices = json_encode([
                    "deluxe" => $deluxe,
                    "double" => $double,
                    "single" => $single
                ], JSON_PRETTY_PRINT);
                $file = dirname(__DIR__) . '/room_prices.json';
                if (file_put_contents($file, $prices) === false) {
                    $errors[] = "JSON file could not be written.";
                }

                // Also save to DB (only if room_prices table exists)
                try {
                    $db = DB::getInstance();
                    if ($db === null) {
                        throw new \RuntimeException("DB not available.");
                    }
                    $sql  = 'INSERT INTO `room_prices` (`room_type`, `price`)
                             VALUES (:type, :price)
                             ON DUPLICATE KEY UPDATE `price` = :price2';
                    $stmt = $db->prepare($sql);
                    if ($stmt === false) {
                        throw new \RuntimeException("room_prices table not found — run migration_room_prices.sql first.");
                    }
                    foreach (['deluxe' => $deluxe, 'double' => $double, 'single' => $single] as $type => $price) {
                        if (!$stmt->execute([':type' => $type, ':price' => $price, ':price2' => $price])) {
                            $errors[] = "DB update failed for $type.";
                        }
                    }
                } catch (\Throwable $e) {
                    // DB save is optional — JSON is the source of truth until migration is run
                    $errors[] = "DB not updated (" . $e->getMessage() . "). Run migration_room_prices.sql to enable DB storage.";
                }

                if (empty($errors)) {
                    echo Util::displayAlertV1("Room prices updated successfully. Changes will reflect site-wide immediately.", "success");
                } elseif (count($errors) === 1 && strpos($errors[0], 'DB not updated') !== false) {
                    // JSON saved fine; DB is just not set up yet — show success (non-critical)
                    echo Util::displayAlertV1("Room prices updated successfully (JSON). To enable database storage, run <code>db/migration_room_prices.sql</code>.", "success");
                } else {
                    echo Util::displayAlertV1("Error saving prices: " . implode(' ', $errors), "warning");
                }
            }
        }
    } else {
        echo Util::displayAlertV1("Unauthorized.", "danger");
    }
} else {
    echo Util::displayAlertV1("Not allowed.", "danger");
}
