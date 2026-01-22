-- Migration: 011_add_import_history.sql
-- Description: Add import_history table to track all import operations
-- Database: nang_suat
-- Date: 2026-01-21

CREATE TABLE IF NOT EXISTS `import_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    
    -- Basic info
    `ten_file` VARCHAR(255) NOT NULL COMMENT 'Original uploaded filename',
    `kich_thuoc_file` INT(11) DEFAULT 0 COMMENT 'File size in bytes',
    
    -- Who/When
    `import_boi` VARCHAR(50) NOT NULL COMMENT 'ma_nv of user who imported',
    `import_luc` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When import was executed',
    
    -- Preview stats (stored for reference)
    `so_sheets` INT(11) DEFAULT 0 COMMENT 'Total sheets in Excel',
    `so_ma_hang_moi` INT(11) DEFAULT 0 COMMENT 'New ma_hang to create',
    `so_ma_hang_cu` INT(11) DEFAULT 0 COMMENT 'Existing ma_hang to update',
    `so_cong_doan_moi` INT(11) DEFAULT 0 COMMENT 'New cong_doan to create',
    `so_cong_doan_cu` INT(11) DEFAULT 0 COMMENT 'Existing cong_doan referenced',
    `so_routing_moi` INT(11) DEFAULT 0 COMMENT 'New routing entries planned',
    `so_routing_xoa` INT(11) DEFAULT 0 COMMENT 'Routing entries to delete',
    
    -- Result stats (after confirm)
    `ma_hang_da_tao` INT(11) DEFAULT 0 COMMENT 'ma_hang actually created',
    `ma_hang_da_cap_nhat` INT(11) DEFAULT 0 COMMENT 'ma_hang actually updated',
    `cong_doan_da_tao` INT(11) DEFAULT 0 COMMENT 'cong_doan actually created',
    `routing_da_tao` INT(11) DEFAULT 0 COMMENT 'routing actually created',
    `routing_da_xoa` INT(11) DEFAULT 0 COMMENT 'routing actually deleted',
    
    -- Status and errors
    `trang_thai` ENUM('success', 'partial', 'failed') NOT NULL DEFAULT 'success' COMMENT 'Import result status',
    `loi` JSON DEFAULT NULL COMMENT 'Parse errors from preview',
    
    -- Detailed data (for drill-down view)
    `chi_tiet` JSON DEFAULT NULL COMMENT 'Detailed list of what was imported',
    
    -- Timing
    `thoi_gian_xu_ly_ms` INT(11) DEFAULT 0 COMMENT 'Processing time in milliseconds',
    
    PRIMARY KEY (`id`),
    INDEX `idx_import_boi` (`import_boi`),
    INDEX `idx_import_luc` (`import_luc`),
    INDEX `idx_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lịch sử import mã hàng và công đoạn';
