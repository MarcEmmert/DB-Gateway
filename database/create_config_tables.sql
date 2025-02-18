-- Sensor Konfiguration
CREATE TABLE IF NOT EXISTS sensor_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    sensor_type VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    UNIQUE KEY device_sensor (device_id, sensor_type)
);

-- Relais Konfiguration
CREATE TABLE IF NOT EXISTS relay_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    relay_number INT NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    UNIQUE KEY device_relay (device_id, relay_number)
);

-- Kontakt Konfiguration
CREATE TABLE IF NOT EXISTS contact_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    contact_number INT NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    UNIQUE KEY device_contact (device_id, contact_number)
);
