CREATE TABLE booking_calendar_guard (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

INSERT INTO booking_calendar_guard (id) VALUES (1);

CREATE TABLE booking_resources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE availability_rules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    resource_id BIGINT UNSIGNED NOT NULL,
    location VARCHAR(160) NOT NULL,
    mode ENUM('workshop', 'home') NOT NULL,
    starts_utc DATETIME NOT NULL,
    ends_utc DATETIME NOT NULL,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    KEY ix_availability_upcoming (enabled, starts_utc),
    KEY ix_availability_resource (resource_id, starts_utc),
    FOREIGN KEY (resource_id) REFERENCES booking_resources (id),
    CHECK (ends_utc > starts_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE appointments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    service_id BIGINT UNSIGNED NOT NULL,
    slot_id BIGINT UNSIGNED NULL,
    submitted_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    contact_name VARCHAR(140) NOT NULL,
    contact_email VARCHAR(254) NOT NULL,
    contact_phone VARCHAR(40) NOT NULL,
    preference VARCHAR(450) NOT NULL,
    notes TEXT NOT NULL,
    admin_notes TEXT NULL,
    mode ENUM('automatic', 'request') NOT NULL,
    status ENUM('pending', 'proposed', 'confirmed', 'declined', 'cancelled') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_appointments_slots (slot_id, status),
    KEY ix_appointments_status (status, created_at),
    FOREIGN KEY (service_id) REFERENCES services (id),
    FOREIGN KEY (slot_id) REFERENCES availability_rules (id),
    CHECK (status NOT IN ('confirmed', 'proposed') OR slot_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE appointment_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    appointment_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'proposed', 'confirmed', 'declined', 'cancelled') NOT NULL,
    slot_id BIGINT UNSIGNED NULL,
    actor_id BIGINT UNSIGNED NULL,
    occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_appointment_events_history (appointment_id, id),
    FOREIGN KEY (appointment_id) REFERENCES appointments (id),
    FOREIGN KEY (slot_id) REFERENCES availability_rules (id),
    FOREIGN KEY (actor_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quotes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    service_id BIGINT UNSIGNED NOT NULL,
    submitted_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    contact_name VARCHAR(140) NOT NULL,
    contact_email VARCHAR(254) NOT NULL,
    contact_phone VARCHAR(40) NOT NULL,
    contact_method ENUM('email', 'phone') NOT NULL,
    preference VARCHAR(450) NOT NULL,
    notes TEXT NOT NULL,
    admin_notes TEXT NULL,
    reply TEXT NULL,
    proposed_amount DECIMAL(10,2) NULL,
    status ENUM('new', 'review', 'sent', 'accepted', 'declined', 'expired') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_quotes_status (status, created_at),
    FOREIGN KEY (service_id) REFERENCES services (id),
    CHECK (proposed_amount IS NULL OR proposed_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quote_attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    quote_id BIGINT UNSIGNED NOT NULL,
    photo_webp MEDIUMBLOB NOT NULL,
    FOREIGN KEY (quote_id) REFERENCES quotes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
