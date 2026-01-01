CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nguoi_dung_id INT NOT NULL,
    quyen VARCHAR(100) NOT NULL,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    nguoi_tao VARCHAR(50) NULL,
    UNIQUE KEY uk_nguoi_dung_quyen (nguoi_dung_id, quyen),
    INDEX idx_nguoi_dung_id (nguoi_dung_id),
    INDEX idx_quyen (quyen),
    FOREIGN KEY (nguoi_dung_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
