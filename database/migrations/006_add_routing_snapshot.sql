-- Migration: Add routing_snapshot column to bao_cao_nang_suat
-- Purpose: Store routing snapshot at report creation time to preserve historical data

ALTER TABLE bao_cao_nang_suat
ADD COLUMN routing_snapshot JSON NULL AFTER ket_qua_luy_ke;
