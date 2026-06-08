-- Migration: Add room_prices table
  -- Run this once on an existing database to enable DB-backed room price management.

  CREATE TABLE IF NOT EXISTS `room_prices` (
      `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `room_type`       VARCHAR(50) NOT NULL,
      `price_per_night` INT(11) UNSIGNED NOT NULL DEFAULT 0,
      `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `room_prices_type_uindex` (`room_type`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

  -- Seed with default prices (adjust values to match your current prices)
  INSERT INTO `room_prices` (`room_type`, `price_per_night`) VALUES
      ('deluxe', 250),
      ('double', 180),
      ('single', 150)
  ON DUPLICATE KEY UPDATE `price_per_night` = VALUES(`price_per_night`);
  