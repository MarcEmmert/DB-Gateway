-- Backup der bestehenden Daten
CREATE TABLE temp_temperatures AS SELECT * FROM temperatures;
CREATE TABLE temp_relays AS SELECT * FROM relays;
CREATE TABLE temp_status_contacts AS SELECT * FROM status_contacts;

-- Temperaturtabelle umbenennen und neu erstellen
DROP TABLE temperatures;
CREATE TABLE sensor_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    sensor_type ENUM('DS18B20_1', 'DS18B20_2', 'BMP180_TEMP', 'BMP180_PRESSURE') NOT NULL,
    value DECIMAL(8,2) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    INDEX idx_device_timestamp (device_id, timestamp),
    INDEX idx_sensor_type (sensor_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alte Temperaturdaten als DS18B20_1 importieren
INSERT INTO sensor_data (device_id, sensor_type, value, timestamp)
SELECT device_id, 'DS18B20_1', value, timestamp
FROM temp_temperatures;

-- Relais-Tabelle anpassen
DROP TABLE relays;
CREATE TABLE relays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    relay_number TINYINT NOT NULL CHECK (relay_number BETWEEN 1 AND 4),
    name VARCHAR(50) NOT NULL,
    state BOOLEAN DEFAULT FALSE,
    last_changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    UNIQUE KEY unique_device_relay (device_id, relay_number),
    INDEX idx_device_id (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alte Relais als Nummer 1 importieren und restliche erstellen
INSERT INTO relays (device_id, relay_number, name, state, last_changed, created_at)
SELECT device_id, 1, name, state, last_changed, created_at
FROM temp_relays;

-- Restliche Relais (2-4) für existierende Geräte erstellen
INSERT INTO relays (device_id, relay_number, name)
SELECT DISTINCT r.device_id, n.number, CONCAT('Relais ', n.number)
FROM temp_relays r
CROSS JOIN (SELECT 2 AS number UNION SELECT 3 UNION SELECT 4) n;

-- Status-Kontakte-Tabelle anpassen
DROP TABLE status_contacts;
CREATE TABLE status_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    contact_number TINYINT NOT NULL CHECK (contact_number BETWEEN 1 AND 4),
    name VARCHAR(50) NOT NULL,
    state BOOLEAN DEFAULT FALSE,
    last_changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    UNIQUE KEY unique_device_contact (device_id, contact_number),
    INDEX idx_device_id (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alte Kontakte als Nummer 1 importieren und restliche erstellen
INSERT INTO status_contacts (device_id, contact_number, name, state, last_changed, created_at)
SELECT device_id, 1, name, state, last_changed, created_at
FROM temp_status_contacts;

-- Restliche Kontakte (2-4) für existierende Geräte erstellen
INSERT INTO status_contacts (device_id, contact_number, name)
SELECT DISTINCT s.device_id, n.number, CONCAT('Kontakt ', n.number)
FROM temp_status_contacts s
CROSS JOIN (SELECT 2 AS number UNION SELECT 3 UNION SELECT 4) n;

-- Trigger aktualisieren
DROP TRIGGER IF EXISTS update_device_last_seen_temperature;
DROP TRIGGER IF EXISTS update_device_last_seen_relay;
DROP TRIGGER IF EXISTS update_device_last_seen_status;

DELIMITER //

CREATE TRIGGER update_device_last_seen_sensor
AFTER INSERT ON sensor_data
FOR EACH ROW
BEGIN
    UPDATE devices SET last_seen = NEW.timestamp
    WHERE id = NEW.device_id;
END;//

CREATE TRIGGER update_device_last_seen_relay
AFTER UPDATE ON relays
FOR EACH ROW
BEGIN
    UPDATE devices SET last_seen = CURRENT_TIMESTAMP
    WHERE id = NEW.device_id;
END;//

CREATE TRIGGER update_device_last_seen_status
AFTER UPDATE ON status_contacts
FOR EACH ROW
BEGIN
    UPDATE devices SET last_seen = CURRENT_TIMESTAMP
    WHERE id = NEW.device_id;
END;//

DELIMITER ;

-- Temporäre Tabellen löschen
DROP TABLE temp_temperatures;
DROP TABLE temp_relays;
DROP TABLE temp_status_contacts;
