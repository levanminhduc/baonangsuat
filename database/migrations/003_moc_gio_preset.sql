CREATE TABLE IF NOT EXISTS moc_gio_set (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ca_id INT NOT NULL,
    ten_set VARCHAR(100) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ca_id) REFERENCES ca_lam(id),
    INDEX idx_ca_id (ca_id),
    INDEX idx_is_default (ca_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS line_moc_gio_set (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_id INT NOT NULL,
    ca_id INT NOT NULL,
    set_id INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (line_id) REFERENCES line(id),
    FOREIGN KEY (ca_id) REFERENCES ca_lam(id),
    FOREIGN KEY (set_id) REFERENCES moc_gio_set(id),
    UNIQUE KEY uk_line_ca (line_id, ca_id),
    INDEX idx_set_id (set_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE moc_gio 
ADD COLUMN set_id INT NULL AFTER line_id,
ADD INDEX idx_set_id_thu_tu (set_id, thu_tu),
ADD CONSTRAINT fk_moc_gio_set FOREIGN KEY (set_id) REFERENCES moc_gio_set(id);

INSERT INTO moc_gio_set (ca_id, ten_set, is_default)
SELECT DISTINCT ca_id, 'Mặc định', 1
FROM moc_gio
WHERE line_id IS NULL;

UPDATE moc_gio mg
JOIN moc_gio_set mgs ON mgs.ca_id = mg.ca_id AND mgs.is_default = 1
SET mg.set_id = mgs.id
WHERE mg.line_id IS NULL;

INSERT INTO moc_gio_set (ca_id, ten_set, is_default)
SELECT DISTINCT mg.ca_id, CONCAT('LINE ', l.ma_line), 0
FROM moc_gio mg
JOIN line l ON l.id = mg.line_id
WHERE mg.line_id IS NOT NULL;

UPDATE moc_gio mg
JOIN line l ON l.id = mg.line_id
JOIN moc_gio_set mgs ON mgs.ca_id = mg.ca_id AND mgs.ten_set = CONCAT('LINE ', l.ma_line) AND mgs.is_default = 0
SET mg.set_id = mgs.id
WHERE mg.line_id IS NOT NULL;

INSERT INTO line_moc_gio_set (line_id, ca_id, set_id)
SELECT DISTINCT mg.line_id, mg.ca_id, mg.set_id
FROM moc_gio mg
WHERE mg.line_id IS NOT NULL AND mg.set_id IS NOT NULL;
