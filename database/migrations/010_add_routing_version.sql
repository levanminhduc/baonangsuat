-- Migration: Add version column to ma_hang_cong_doan for optimistic locking
-- Date: 2026-01-17

ALTER TABLE ma_hang_cong_doan
ADD COLUMN version INT UNSIGNED NOT NULL DEFAULT 1 AFTER ghi_chu;

-- Update existing rows to have version 1
UPDATE ma_hang_cong_doan SET version = 1 WHERE version IS NULL OR version = 0;
