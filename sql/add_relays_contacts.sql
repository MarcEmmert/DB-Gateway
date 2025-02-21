-- Füge Relais für bestehende Geräte hinzu
INSERT INTO relays (device_id, relay_number, name, state)
SELECT d.id, r.relay_number, CONCAT('Relais ', r.relay_number), 0
FROM devices d
CROSS JOIN (
    SELECT 1 as relay_number UNION ALL
    SELECT 2 UNION ALL
    SELECT 3 UNION ALL
    SELECT 4
) r
WHERE NOT EXISTS (
    SELECT 1 
    FROM relays 
    WHERE device_id = d.id AND relay_number = r.relay_number
);

-- Füge Relais-Konfiguration hinzu
INSERT INTO relay_config (device_id, relay_number, display_name)
SELECT d.id, r.relay_number, CONCAT('Relais ', r.relay_number)
FROM devices d
CROSS JOIN (
    SELECT 1 as relay_number UNION ALL
    SELECT 2 UNION ALL
    SELECT 3 UNION ALL
    SELECT 4
) r
WHERE NOT EXISTS (
    SELECT 1 
    FROM relay_config 
    WHERE device_id = d.id AND relay_number = r.relay_number
);

-- Füge Kontakte für bestehende Geräte hinzu
INSERT INTO status_contacts (device_id, contact_number, name, state)
SELECT d.id, c.contact_number, CONCAT('Kontakt ', c.contact_number), 0
FROM devices d
CROSS JOIN (
    SELECT 1 as contact_number UNION ALL
    SELECT 2 UNION ALL
    SELECT 3 UNION ALL
    SELECT 4
) c
WHERE NOT EXISTS (
    SELECT 1 
    FROM status_contacts 
    WHERE device_id = d.id AND contact_number = c.contact_number
);

-- Füge Kontakt-Konfiguration hinzu
INSERT INTO contact_config (device_id, contact_number, display_name)
SELECT d.id, c.contact_number, CONCAT('Kontakt ', c.contact_number)
FROM devices d
CROSS JOIN (
    SELECT 1 as contact_number UNION ALL
    SELECT 2 UNION ALL
    SELECT 3 UNION ALL
    SELECT 4
) c
WHERE NOT EXISTS (
    SELECT 1 
    FROM contact_config 
    WHERE device_id = d.id AND contact_number = c.contact_number
);
