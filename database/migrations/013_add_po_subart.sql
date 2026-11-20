-- Migration: 013_add_po_subart.sql
-- Description: Gắn PO / Subart (master data datchi) vào báo cáo; SL PO – Subart nhập tay.
--   Khoá trùng báo cáo thêm PO + subart: một LINE may nhiều PO/subart của cùng mã hàng trong một ca.
--   po_key/sub_art_key là cột sinh vì UNIQUE coi NULL là khác nhau (báo cáo không PO vẫn phải bị chặn trùng).
-- Database: nang_suat
-- Date: 2026-11-14

ALTER TABLE bao_cao_nang_suat
    ADD COLUMN po_number VARCHAR(50) NULL AFTER ma_hang_id,
    ADD COLUMN sub_art_code VARCHAR(100) NULL AFTER po_number,
    ADD COLUMN po_key VARCHAR(50) AS (IFNULL(po_number, '')) STORED,
    ADD COLUMN sub_art_key VARCHAR(100) AS (IFNULL(sub_art_code, '')) STORED;

ALTER TABLE bao_cao_nang_suat
    DROP INDEX uk_bao_cao,
    ADD UNIQUE KEY uk_bao_cao (ngay_bao_cao, line_id, ca_id, ma_hang_id, po_key, sub_art_key),
    ADD KEY idx_po_ma_hang (po_number, ma_hang_id, sub_art_code);

CREATE TABLE IF NOT EXISTS po_subart_so_luong (
    po_number VARCHAR(50) NOT NULL,
    ma_hang_id INT NOT NULL,
    sub_art_code VARCHAR(100) NOT NULL,
    so_luong INT UNSIGNED NOT NULL,
    updated_by VARCHAR(50) DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (po_number, ma_hang_id, sub_art_code),
    KEY ma_hang_id (ma_hang_id),
    FOREIGN KEY (ma_hang_id) REFERENCES ma_hang(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
