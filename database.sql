-- ============================================================
--  TripWeave Database Schema + Sample Data
--  Database: tripweave
--
--  How to import:
--  1. Open phpMyAdmin at http://localhost/phpmyadmin
--  2. Click "Import" → choose this file → click "Go"
--  OR via command line:
--     mysql -u root -p < database.sql
-- ============================================================

-- Create & select the database
CREATE DATABASE IF NOT EXISTS `tripweave`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `tripweave`;

-- ── TABLE: users ────────────────────────────────────
-- Stores registered TripWeave users.
-- Passwords are stored as bcrypt hashes — NEVER in plain text.
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(40)  NOT NULL,
  `email`      VARCHAR(120) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,   -- bcrypt hash
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY   `uq_username` (`username`),
  UNIQUE KEY   `uq_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABLE: trips ────────────────────────────────────
-- One row per trip planned by a user.
CREATE TABLE IF NOT EXISTS `trips` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `user_id`    INT          NOT NULL,
  `trip_name`  VARCHAR(120) NOT NULL,
  `start_date` DATE         NOT NULL,
  `end_date`   DATE         NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY          `idx_trips_user` (`user_id`),
  CONSTRAINT   `fk_trips_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABLE: itinerary_stops ──────────────────────────
-- Individual stops/activities within a trip, keyed by day number.
CREATE TABLE IF NOT EXISTS `itinerary_stops` (
  `id`          INT          NOT NULL AUTO_INCREMENT,
  `trip_id`     INT          NOT NULL,
  `day_number`  TINYINT      NOT NULL DEFAULT 1,
  `stop_name`   VARCHAR(150) NOT NULL,
  `notes`       TEXT             NULL,
  `stop_time`   TIME             NULL,   -- optional scheduled time
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY   (`id`),
  KEY           `idx_stops_trip` (`trip_id`),
  CONSTRAINT    `fk_stops_trip`
    FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABLE: messages ─────────────────────────────────
-- Contact form submissions.
CREATE TABLE IF NOT EXISTS `messages` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80)  NOT NULL,
  `email`      VARCHAR(120) NOT NULL,
  `subject`    VARCHAR(180)     NULL,   -- optional subject line from contact form
  `message`    TEXT         NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  SAMPLE DATA
--  Note: The sample password hash below is for the string
--  "Password1" — generated with PHP's password_hash().
--  Change immediately after importing.
-- ============================================================

-- Sample user (password: "Password1")
INSERT INTO `users` (`username`, `email`, `password`, `created_at`) VALUES
('alex_explorer',
 'alex@example.com',
 '$2y$12$3eHMZj6V7ZxUqF8RbXqXnOjwNk.3gDKZ3wP1xFnH4V5OQXvuXqBHO',
 '2025-08-01 10:00:00');

-- Sample trip 1: Kyoto Adventure
INSERT INTO `trips` (`user_id`, `trip_name`, `start_date`, `end_date`, `created_at`) VALUES
(1, 'Kyoto Autumn Adventure', '2025-11-10', '2025-11-14', '2025-08-05 09:00:00');

-- Sample stops for Kyoto trip (trip_id = 1)
INSERT INTO `itinerary_stops` (`trip_id`, `day_number`, `stop_name`, `notes`, `stop_time`, `created_at`) VALUES
(1, 1, 'Fushimi Inari Shrine',
 'Arrive early to beat the crowds. Wear comfortable shoes for the hike.',
 '08:00:00', '2025-08-05 09:05:00'),
(1, 1, 'Nishiki Market',
 'Try the street food stalls. Best tamagoyaki in Kyoto.',
 '12:30:00', '2025-08-05 09:06:00'),
(1, 1, 'Gion District Evening Walk',
 'Spot geishas in Hanamikoji Street after 6pm.',
 '18:00:00', '2025-08-05 09:07:00'),
(1, 2, 'Arashiyama Bamboo Grove',
 'Go at sunrise for the best photos.',
 '06:30:00', '2025-08-05 09:08:00'),
(1, 2, 'Tenryu-ji Temple & Garden',
 'UNESCO World Heritage Site. Entry ¥500.',
 '09:00:00', '2025-08-05 09:09:00'),
(1, 3, 'Kinkaku-ji (Golden Pavilion)',
 'Book tickets online in advance.',
 '09:30:00', '2025-08-05 09:10:00'),
(1, 3, 'Philosopher''s Path',
 'Beautiful canal walk — peak foliage in November.',
 '14:00:00', '2025-08-05 09:11:00');

-- Sample trip 2: Greece Getaway
INSERT INTO `trips` (`user_id`, `trip_name`, `start_date`, `end_date`, `created_at`) VALUES
(1, 'Santorini & Athens', '2026-06-15', '2026-06-21', '2025-08-10 11:00:00');

-- Sample stops for Greece trip (trip_id = 2)
INSERT INTO `itinerary_stops` (`trip_id`, `day_number`, `stop_name`, `notes`, `stop_time`, `created_at`) VALUES
(2, 1, 'Acropolis of Athens',
 'Arrive before 9am. Audio guide worth it.',
 '08:30:00', '2025-08-10 11:05:00'),
(2, 1, 'Monastiraki Flea Market',
 'Great for souvenirs and street food.',
 '13:00:00', '2025-08-10 11:06:00'),
(2, 2, 'Ferry to Santorini',
 'High-speed ferry from Piraeus port, 5 hours.',
 '07:00:00', '2025-08-10 11:07:00'),
(2, 3, 'Oia Sunset Viewpoint',
 'Best sunset in the world. Get there 1 hour early.',
 '19:00:00', '2025-08-10 11:08:00'),
(2, 4, 'Red Beach Hike',
 'Wear water shoes. 20-min hike from parking.',
 '10:00:00', '2025-08-10 11:09:00');

-- Sample contact message
INSERT INTO `messages` (`name`, `email`, `subject`, `message`, `created_at`) VALUES
('Maria Santos',
 'maria@example.com',
 'Feature Request',
 'I love TripWeave! Could you add a packing list feature to each trip? That would be amazing.',
 '2025-08-20 14:32:00');
