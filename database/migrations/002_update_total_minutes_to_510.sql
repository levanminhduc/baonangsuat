UPDATE moc_gio 
SET so_phut_hieu_dung_luy_ke = 330 
WHERE gio = '14:00:00' 
  AND line_id IS NULL 
  AND so_phut_hieu_dung_luy_ke = 300;

UPDATE moc_gio 
SET so_phut_hieu_dung_luy_ke = 510 
WHERE gio = '17:00:00' 
  AND line_id IS NULL 
  AND so_phut_hieu_dung_luy_ke = 480;
