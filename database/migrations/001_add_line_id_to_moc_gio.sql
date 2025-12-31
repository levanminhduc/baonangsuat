USE nang_suat;

ALTER TABLE moc_gio 
ADD COLUMN line_id INT DEFAULT NULL AFTER ca_id;

ALTER TABLE moc_gio
ADD CONSTRAINT fk_moc_gio_line 
    FOREIGN KEY (line_id) REFERENCES line(id) ON DELETE CASCADE;

CREATE INDEX idx_moc_gio_line ON moc_gio(line_id);
CREATE INDEX idx_moc_gio_ca_line ON moc_gio(ca_id, line_id);

ALTER TABLE moc_gio
ADD CONSTRAINT uk_moc_gio_ca_line_gio 
    UNIQUE (ca_id, line_id, gio);

INSERT INTO moc_gio (ca_id, line_id, gio, thu_tu, so_phut_hieu_dung_luy_ke) 
SELECT 1, l.id, '09:00:00', 1, 90
FROM line l WHERE l.ma_line IN ('LINE_4', 'LINE_5');

INSERT INTO moc_gio (ca_id, line_id, gio, thu_tu, so_phut_hieu_dung_luy_ke) 
SELECT 1, l.id, '11:00:00', 2, 210
FROM line l WHERE l.ma_line IN ('LINE_4', 'LINE_5');

INSERT INTO moc_gio (ca_id, line_id, gio, thu_tu, so_phut_hieu_dung_luy_ke) 
SELECT 1, l.id, '14:00:00', 3, 330
FROM line l WHERE l.ma_line IN ('LINE_4', 'LINE_5');

INSERT INTO moc_gio (ca_id, line_id, gio, thu_tu, so_phut_hieu_dung_luy_ke) 
SELECT 1, l.id, '17:00:00', 4, 510
FROM line l WHERE l.ma_line IN ('LINE_4', 'LINE_5');
