-- Sprint 3: Member meal counts (new table — NOT meal_expenses/bazar)
-- Run once in phpMyAdmin on database: mess manager 2.0

CREATE TABLE IF NOT EXISTS `meals` (
  `meal_id` int(11) NOT NULL AUTO_INCREMENT,
  `mess_id` int(11) NOT NULL,
  `month_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_date` date NOT NULL,
  `breakfast` decimal(4,1) NOT NULL DEFAULT 0.0,
  `lunch` decimal(4,1) NOT NULL DEFAULT 0.0,
  `dinner` decimal(4,1) NOT NULL DEFAULT 0.0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`meal_id`),
  UNIQUE KEY `unique_member_meal_day` (`mess_id`, `month_id`, `user_id`, `meal_date`),
  KEY `idx_meals_mess_month` (`mess_id`, `month_id`),
  KEY `idx_meals_date` (`meal_date`),
  KEY `idx_meals_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
