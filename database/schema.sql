CREATE DATABASE IF NOT EXISTS nang_suat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nang_suat;

CREATE TABLE line (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_line VARCHAR(50) NOT NULL UNIQUE,
    ten_line VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE phong_ban_line (
    phong_ban_ma VARCHAR(50) NOT NULL,
    line_id INT NOT NULL,
    PRIMARY KEY (phong_ban_ma, line_id),
    FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_line (
    ma_nv VARCHAR(50) NOT NULL,
    line_id INT NOT NULL,
    PRIMARY KEY (ma_nv, line_id),
    FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ma_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_hang VARCHAR(50) NOT NULL UNIQUE,
    ten_hang VARCHAR(200),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE cong_doan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_cong_doan VARCHAR(50) UNIQUE,
    ten_cong_doan VARCHAR(200) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    la_cong_doan_thanh_pham TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE ma_hang_cong_doan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_hang_id INT NOT NULL,
    line_id INT DEFAULT NULL,
    cong_doan_id INT NOT NULL,
    thu_tu INT NOT NULL DEFAULT 0,
    bat_buoc TINYINT(1) DEFAULT 1,
    la_cong_doan_tinh_luy_ke TINYINT(1) DEFAULT 0,
    hieu_luc_tu DATE DEFAULT NULL,
    hieu_luc_den DATE DEFAULT NULL,
    ghi_chu TEXT,
    FOREIGN KEY (ma_hang_id) REFERENCES ma_hang(id) ON DELETE CASCADE,
    FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE SET NULL,
    FOREIGN KEY (cong_doan_id) REFERENCES cong_doan(id) ON DELETE CASCADE,
    INDEX idx_ma_hang_line (ma_hang_id, line_id),
    INDEX idx_thu_tu (thu_tu)
) ENGINE=InnoDB;

CREATE TABLE ca_lam (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_ca VARCHAR(20) NOT NULL UNIQUE,
    ten_ca VARCHAR(100) NOT NULL,
    gio_bat_dau TIME,
    gio_ket_thuc TIME,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE moc_gio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ca_id INT NOT NULL,
    line_id INT DEFAULT NULL,
    gio TIME NOT NULL,
    thu_tu INT NOT NULL DEFAULT 0,
    so_phut_hieu_dung_luy_ke INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (ca_id) REFERENCES ca_lam(id) ON DELETE CASCADE,
    FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE CASCADE,
    INDEX idx_ca_thu_tu (ca_id, thu_tu),
    INDEX idx_moc_gio_line (line_id),
    INDEX idx_moc_gio_ca_line (ca_id, line_id),
    UNIQUE KEY uk_moc_gio_ca_line_gio (ca_id, line_id, gio)
) ENGINE=InnoDB;

CREATE TABLE bao_cao_nang_suat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ngay_bao_cao DATE NOT NULL,
    line_id INT NOT NULL,
    ca_id INT NOT NULL,
    ma_hang_id INT NOT NULL,
    so_lao_dong INT NOT NULL DEFAULT 0,
    ctns INT NOT NULL DEFAULT 0,
    ct_gio DECIMAL(10,2) DEFAULT 0,
    tong_phut_hieu_dung INT DEFAULT 0,
    ghi_chu TEXT,
    trang_thai ENUM('draft', 'submitted', 'approved', 'locked', 'completed') DEFAULT 'draft',
    version INT DEFAULT 1,
    tao_boi VARCHAR(50),
    tao_luc TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cap_nhat_luc TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ket_qua_luy_ke JSON NULL,
    routing_snapshot JSON NULL,
    hoan_tat_luc TIMESTAMP NULL,
    hoan_tat_boi VARCHAR(50) NULL,
    FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE RESTRICT,
    FOREIGN KEY (ca_id) REFERENCES ca_lam(id) ON DELETE RESTRICT,
    FOREIGN KEY (ma_hang_id) REFERENCES ma_hang(id) ON DELETE RESTRICT,
    UNIQUE KEY uk_bao_cao (ngay_bao_cao, line_id, ca_id, ma_hang_id),
    INDEX idx_ngay (ngay_bao_cao),
    INDEX idx_line (line_id),
    INDEX idx_trang_thai (trang_thai),
    INDEX idx_ma_hang (ma_hang_id)
) ENGINE=InnoDB;

CREATE TABLE nhap_lieu_nang_suat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bao_cao_id INT NOT NULL,
    cong_doan_id INT NOT NULL,
    moc_gio_id INT NOT NULL,
    so_luong INT NOT NULL DEFAULT 0,
    kieu_nhap ENUM('tang_them', 'luy_ke') DEFAULT 'tang_them',
    ghi_chu TEXT,
    nhap_boi VARCHAR(50),
    nhap_luc TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bao_cao_id) REFERENCES bao_cao_nang_suat(id) ON DELETE CASCADE,
    FOREIGN KEY (cong_doan_id) REFERENCES cong_doan(id) ON DELETE RESTRICT,
    FOREIGN KEY (moc_gio_id) REFERENCES moc_gio(id) ON DELETE RESTRICT,
    UNIQUE KEY uk_entry (bao_cao_id, cong_doan_id, moc_gio_id),
    INDEX idx_bao_cao (bao_cao_id),
    INDEX idx_cong_doan (cong_doan_id)
) ENGINE=InnoDB;

CREATE TABLE nhap_lieu_nang_suat_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT NOT NULL,
    old_value INT,
    new_value INT,
    updated_by VARCHAR(50),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    INDEX idx_entry (entry_id),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB;

INSERT INTO ca_lam (ma_ca, ten_ca, gio_bat_dau, gio_ket_thuc) VALUES
('CA', 'Ca làm việc', '07:30:00', '17:00:00');

INSERT INTO moc_gio (ca_id, gio, thu_tu, so_phut_hieu_dung_luy_ke) VALUES
(1, '09:00:00', 1, 90),
(1, '11:00:00', 2, 210),
(1, '14:00:00', 3, 330),
(1, '17:00:00', 4, 510);

INSERT INTO line (ma_line, ten_line) VALUES
('LINE_1', 'Chuyền 1'),
('LINE_2', 'Chuyền 2'),
('LINE_3', 'Chuyền 3'),
('LINE_4', 'Chuyền 4'),
('LINE_5', 'Chuyền 5'),
('01DK', 'Chuyền 01DK');

INSERT INTO cong_doan (ma_cong_doan, ten_cong_doan, la_cong_doan_thanh_pham) VALUES
('CD01', 'Cắt', 0),
('CD02', 'May', 0),
('CD03', 'Là', 0),
('CD04', 'Đóng gói', 0),
('CD05', 'KCS thành phẩm', 1);

INSERT INTO ma_hang (ma_hang, ten_hang) VALUES
('6175', 'Áo sơ mi nam'),
('6176', 'Quần tây nam'),
('6177', 'Áo vest nữ');

INSERT INTO ma_hang_cong_doan (ma_hang_id, cong_doan_id, thu_tu, la_cong_doan_tinh_luy_ke) VALUES
(1, 1, 1, 0),
(1, 2, 2, 0),
(1, 3, 3, 0),
(1, 4, 4, 0),
(1, 5, 5, 1),
(2, 1, 1, 0),
(2, 2, 2, 0),
(2, 4, 3, 0),
(2, 5, 4, 1),
(3, 1, 1, 0),
(3, 2, 2, 0),
(3, 3, 3, 0),
(3, 5, 4, 1);

INSERT INTO user_line (ma_nv, line_id) VALUES
('4211', 6);

INSERT INTO moc_gio (ca_id, line_id, gio, thu_tu, so_phut_hieu_dung_luy_ke) VALUES
(1, 4, '09:00:00', 1, 90),
(1, 4, '11:00:00', 2, 210),
(1, 4, '14:00:00', 3, 330),
(1, 4, '17:00:00', 4, 510),
(1, 5, '09:00:00', 1, 90),
(1, 5, '11:00:00', 2, 210),
(1, 5, '14:00:00', 3, 330),
(1, 5, '17:00:00', 4, 510);
