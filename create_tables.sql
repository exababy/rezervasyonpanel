CREATE TABLE IF NOT EXISTS reservations (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	table_id INT UNSIGNED NOT NULL DEFAULT 1,
	customer_type VARCHAR(50) NOT NULL DEFAULT 'Bireysel',
	customer_name VARCHAR(150) NOT NULL,
	customer_phone VARCHAR(30) DEFAULT NULL,
	person_count INT UNSIGNED NOT NULL DEFAULT 1,
	child_count INT UNSIGNED NOT NULL DEFAULT 0,
	reservation_date DATE NOT NULL,
	reservation_time TIME NOT NULL,
	status VARCHAR(30) NOT NULL DEFAULT 'Bekliyor',
	notes TEXT,
	price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

	INDEX idx_reservation_date (reservation_date),
	INDEX idx_reservation_datetime (reservation_date, reservation_time),
	INDEX idx_customer_phone (customer_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
