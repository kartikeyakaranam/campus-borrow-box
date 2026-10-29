-- Import this into the database already created by the hosting provider.
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL,
  title VARCHAR(100) NOT NULL,
  category ENUM('Books','Electronics','Lab','Other') NOT NULL,
  description TEXT NOT NULL,
  pickup_location VARCHAR(100) NOT NULL,
  borrowing_fee DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  pricing_mode ENUM('flat','hourly') NOT NULL DEFAULT 'flat',
  hourly_rate DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  minimum_hours SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  image_filename VARCHAR(80) NULL,
  status ENUM('available','lent') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  borrower_id INT NOT NULL,
  message VARCHAR(250) NOT NULL,
  agreed_fee DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  pricing_mode ENUM('flat','hourly') NOT NULL DEFAULT 'flat',
  agreed_rate DECIMAL(8,2) NULL,
  billable_hours SMALLINT UNSIGNED NULL,
  start_at DATETIME NULL,
  end_at DATETIME NULL,
  payment_method ENUM('cash','upi') NULL,
  paid_at DATETIME NULL,
  due_date DATE NULL,
  status ENUM('pending','approved','declined','returned') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (borrower_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX item_borrower_status (item_id, borrower_id, status)
);
