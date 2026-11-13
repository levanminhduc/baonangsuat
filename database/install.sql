-- Cài mới DB năng suất (prod: bao_nang_suat; local: nang_suat) — đã gộp schema.sql + migrations 001–011.
-- Chạy được trên MySQL 8.x và MariaDB 10.4+. Chỉ dùng cho DB TRỐNG.
-- Toàn bộ bảng dùng utf8mb4_unicode_ci: trộn general_ci/unicode_ci làm lỗi so sánh chuỗi
-- (migration 003 gặp lỗi "Illegal mix of collations" vì vậy).
-- Tên DB phải khớp 'database_nang_suat' trong C:/xampp/config/db.php.

CREATE DATABASE IF NOT EXISTS bao_nang_suat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bao_nang_suat;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE line (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_line VARCHAR(50) NOT NULL,
    ten_line VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY ma_line (ma_line)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE phong_ban_line (
    phong_ban_ma VARCHAR(50) NOT NULL,
    line_id INT NOT NULL,
    PRIMARY KEY (phong_ban_ma, line_id),
    KEY line_id (line_id),
    FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_line (
    ma_nv VARCHAR(50) NOT NULL,
    line_id INT NOT NULL,
    PRIMARY KEY (ma_nv, line_id),
    KEY line_id (line_id),
    FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ma_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_hang VARCHAR(50) NOT NULL,
    ten_hang VARCHAR(200) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY ma_hang (ma_hang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cong_doan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_cong_doan VARCHAR(50) DEFAULT NULL,
    ten_cong_doan VARCHAR(200) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    la_cong_doan_thanh_pham TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY ma_cong_doan (ma_cong_doan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- version: optimistic locking cho routing (migration 010_add_routing_version)
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
    version INT UNSIGNED NOT NULL DEFAULT 1,
    KEY line_id (line_id),
    KEY cong_doan_id (cong_doan_id),
    KEY idx_ma_hang_line (ma_hang_id, line_id),
    KEY idx_thu_tu (thu_tu),
    FOREIGN KEY (ma_hang_id) REFERENCES ma_hang(id) ON DELETE CASCADE,
    FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE SET NULL,
    FOREIGN KEY (cong_doan_id) REFERENCES cong_doan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ca_lam (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_ca VARCHAR(20) NOT NULL,
    ten_ca VARCHAR(100) NOT NULL,
    gio_bat_dau TIME DEFAULT NULL,
    gio_ket_thuc TIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY ma_ca (ma_ca)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bộ mốc giờ (migration 003)
CREATE TABLE moc_gio_set (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ca_id INT NOT NULL,
    ten_set VARCHAR(100) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ca_id (ca_id),
    KEY idx_is_default (ca_id, is_default),
    FOREIGN KEY (ca_id) REFERENCES ca_lam(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE line_moc_gio_set (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_id INT NOT NULL,
    ca_id INT NOT NULL,
    set_id INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_line_ca (line_id, ca_id),
    KEY ca_id (ca_id),
    KEY idx_set_id (set_id),
    FOREIGN KEY (line_id) REFERENCES line(id),
    FOREIGN KEY (ca_id) REFERENCES ca_lam(id),
    FOREIGN KEY (set_id) REFERENCES moc_gio_set(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE moc_gio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ca_id INT NOT NULL,
    line_id INT DEFAULT NULL,
    set_id INT DEFAULT NULL,
    gio TIME NOT NULL,
    thu_tu INT NOT NULL DEFAULT 0,
    so_phut_hieu_dung_luy_ke INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    UNIQUE KEY uk_moc_gio_ca_line_gio (ca_id, line_id, gio),
    KEY idx_ca_thu_tu (ca_id, thu_tu),
    KEY idx_moc_gio_line (line_id),
    KEY idx_moc_gio_ca_line (ca_id, line_id),
    KEY idx_set_id_thu_tu (set_id, thu_tu),
    CONSTRAINT fk_moc_gio_line FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE CASCADE,
    CONSTRAINT fk_moc_gio_set FOREIGN KEY (set_id) REFERENCES moc_gio_set(id),
    FOREIGN KEY (ca_id) REFERENCES ca_lam(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ket_qua_luy_ke (005), routing_snapshot (006), completed + hoan_tat_* (010)
CREATE TABLE bao_cao_nang_suat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ngay_bao_cao DATE NOT NULL,
    line_id INT NOT NULL,
    ca_id INT NOT NULL,
    ma_hang_id INT NOT NULL,
    so_lao_dong INT NOT NULL DEFAULT 0,
    ctns INT NOT NULL DEFAULT 0,
    ct_gio DECIMAL(10,2) DEFAULT 0.00,
    tong_phut_hieu_dung INT DEFAULT 0,
    ghi_chu TEXT,
    trang_thai ENUM('draft', 'submitted', 'approved', 'locked', 'completed') DEFAULT 'draft',
    version INT DEFAULT 1,
    tao_boi VARCHAR(50) DEFAULT NULL,
    tao_luc TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    cap_nhat_luc TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    hoan_tat_luc TIMESTAMP NULL DEFAULT NULL,
    hoan_tat_boi VARCHAR(50) DEFAULT NULL,
    ket_qua_luy_ke JSON NULL,
    routing_snapshot JSON NULL,
    UNIQUE KEY uk_bao_cao (ngay_bao_cao, line_id, ca_id, ma_hang_id),
    KEY ca_id (ca_id),
    KEY ma_hang_id (ma_hang_id),
    KEY idx_ngay (ngay_bao_cao),
    KEY idx_line (line_id),
    KEY idx_trang_thai (trang_thai),
    FOREIGN KEY (line_id) REFERENCES line(id),
    FOREIGN KEY (ca_id) REFERENCES ca_lam(id),
    FOREIGN KEY (ma_hang_id) REFERENCES ma_hang(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nhap_lieu_nang_suat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bao_cao_id INT NOT NULL,
    cong_doan_id INT NOT NULL,
    moc_gio_id INT NOT NULL,
    so_luong INT NOT NULL DEFAULT 0,
    kieu_nhap ENUM('tang_them', 'luy_ke') DEFAULT 'tang_them',
    ghi_chu TEXT,
    nhap_boi VARCHAR(50) DEFAULT NULL,
    nhap_luc TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_entry (bao_cao_id, cong_doan_id, moc_gio_id),
    KEY moc_gio_id (moc_gio_id),
    KEY idx_bao_cao (bao_cao_id),
    KEY idx_cong_doan (cong_doan_id),
    FOREIGN KEY (bao_cao_id) REFERENCES bao_cao_nang_suat(id) ON DELETE CASCADE,
    FOREIGN KEY (cong_doan_id) REFERENCES cong_doan(id),
    FOREIGN KEY (moc_gio_id) REFERENCES moc_gio(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nhap_lieu_nang_suat_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT NOT NULL,
    old_value INT DEFAULT NULL,
    new_value INT DEFAULT NULL,
    updated_by VARCHAR(50) DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    KEY idx_entry (entry_id),
    KEY idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lịch sử import (migration 011)
CREATE TABLE import_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_file VARCHAR(255) NOT NULL,
    kich_thuoc_file INT DEFAULT 0,
    import_boi VARCHAR(50) NOT NULL,
    import_luc TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    so_sheets INT DEFAULT 0,
    so_ma_hang_moi INT DEFAULT 0,
    so_ma_hang_cu INT DEFAULT 0,
    so_cong_doan_moi INT DEFAULT 0,
    so_cong_doan_cu INT DEFAULT 0,
    so_routing_moi INT DEFAULT 0,
    so_routing_xoa INT DEFAULT 0,
    ma_hang_da_tao INT DEFAULT 0,
    ma_hang_da_cap_nhat INT DEFAULT 0,
    cong_doan_da_tao INT DEFAULT 0,
    routing_da_tao INT DEFAULT 0,
    routing_da_xoa INT DEFAULT 0,
    trang_thai ENUM('success', 'partial', 'failed') NOT NULL DEFAULT 'success',
    loi JSON DEFAULT NULL,
    chi_tiet JSON DEFAULT NULL,
    thoi_gian_xu_ly_ms INT DEFAULT 0,
    KEY idx_import_boi (import_boi),
    KEY idx_import_luc (import_luc),
    KEY idx_trang_thai (trang_thai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu tối thiểu để app chạy: 1 ca + bộ mốc giờ mặc định (510 phút, migration 002).
-- LINE, mã hàng, công đoạn, phân quyền LINE nhập qua trang admin / import Excel.
INSERT INTO ca_lam (id, ma_ca, ten_ca, gio_bat_dau, gio_ket_thuc) VALUES
(1, 'CA', 'Ca làm việc', '07:30:00', '17:00:00');

INSERT INTO moc_gio_set (id, ca_id, ten_set, is_default) VALUES
(1, 1, 'Mặc định', 1);

INSERT INTO moc_gio (ca_id, line_id, set_id, gio, thu_tu, so_phut_hieu_dung_luy_ke) VALUES
(1, NULL, 1, '09:00:00', 1, 90),
(1, NULL, 1, '11:00:00', 2, 210),
(1, NULL, 1, '14:00:00', 3, 330),
(1, NULL, 1, '17:00:00', 4, 510);
