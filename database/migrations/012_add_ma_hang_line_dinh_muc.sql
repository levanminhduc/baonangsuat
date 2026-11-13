-- Migration: 012_add_ma_hang_line_dinh_muc.sql
-- Description: Lưu LĐ + CTNS theo mã hàng + LINE (đọc từ file import) để tự điền khi tạo báo cáo
-- Database: nang_suat
-- Date: 2026-11-13

CREATE TABLE IF NOT EXISTS ma_hang_line_dinh_muc (
    ma_hang_id INT NOT NULL,
    line_id INT NOT NULL,
    so_lao_dong INT NOT NULL DEFAULT 0,
    ctns INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ma_hang_id, line_id),
    KEY line_id (line_id),
    FOREIGN KEY (ma_hang_id) REFERENCES ma_hang(id) ON DELETE CASCADE,
    FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
