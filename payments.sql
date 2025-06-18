-- Drop existing table if it exists
DROP TABLE IF EXISTS payments;

CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    card_last_four VARCHAR(4),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    refund_reason TEXT,
    refund_date DATETIME,
    refund_amount DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add payment_id column to bookings table
ALTER TABLE bookings 
ADD COLUMN payment_id INT,
ADD FOREIGN KEY (payment_id) REFERENCES payments(id); 