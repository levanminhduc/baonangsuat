ALTER TABLE bao_cao_nang_suat 
MODIFY COLUMN trang_thai ENUM('draft', 'submitted', 'approved', 'locked', 'completed') 
DEFAULT 'draft';

ALTER TABLE bao_cao_nang_suat 
ADD COLUMN hoan_tat_luc TIMESTAMP NULL AFTER cap_nhat_luc,
ADD COLUMN hoan_tat_boi VARCHAR(50) NULL AFTER hoan_tat_luc;