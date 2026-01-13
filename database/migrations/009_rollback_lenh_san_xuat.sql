ALTER TABLE bao_cao_nang_suat DROP FOREIGN KEY IF EXISTS fk_bao_cao_lenh_sx;

ALTER TABLE bao_cao_nang_suat DROP COLUMN IF EXISTS lenh_sx_id;

DROP TABLE IF EXISTS lenh_san_xuat;