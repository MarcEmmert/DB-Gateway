-- Benutzer-Tabelle
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Geräte-Tabelle
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    mqtt_topic VARCHAR(100) NOT NULL UNIQUE,
    last_seen TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_mqtt_topic (mqtt_topic)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sensor-Daten-Tabelle
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

-- Relais-Tabelle
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

-- Status-Kontakte-Tabelle
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

-- API-Tokens-Tabelle für Mobile App
CREATE TABLE api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger für die Aktualisierung von last_seen in devices
DELIMITER //
CREATE TRIGGER update_device_last_seen_temperature
AFTER INSERT ON temperatures
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

CREATE TRIGGER update_device_last_seen_sensor
AFTER INSERT ON sensor_data
FOR EACH ROW
BEGIN
    UPDATE devices SET last_seen = CURRENT_TIMESTAMP
    WHERE id = NEW.device_id;
END;//
DELIMITER ;

-- Beispiel-Admin-Benutzer (Passwort muss geändert werden!)
INSERT INTO users (username, password, email, is_admin) VALUES 
('admin', '$2y$10$YourHashedPasswordHere', 'admin@example.com', TRUE);
