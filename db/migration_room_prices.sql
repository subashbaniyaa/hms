-- Run this once against your `hotel` database before using the updated room price feature.
-- It creates the room_prices table and seeds it with default values.

CREATE TABLE IF NOT EXISTS `room_prices` (
    `room_type` VARCHAR(20) NOT NULL,
    `price`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`room_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Seed with defaults (safe to re-run — ON DUPLICATE KEY does nothing if values already exist)
INSERT INTO `room_prices` (`room_type`, `price`) VALUES
    ('deluxe', 250.00),
    ('double', 180.00),
    ('single', 150.00)
ON DUPLICATE KEY UPDATE `room_type` = `room_type`;
