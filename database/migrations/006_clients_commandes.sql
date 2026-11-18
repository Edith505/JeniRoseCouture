CREATE TABLE clients (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    notes TEXT NULL,
    archived_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clients_email (email),
    KEY ix_clients_status_created (archived_at, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE client_addresses (
    client_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    address_line VARCHAR(180) NOT NULL,
    postal_code VARCHAR(16) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(80) NOT NULL,
    FOREIGN KEY (client_id) REFERENCES clients (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE appointments ADD COLUMN client_id BIGINT UNSIGNED NULL,
    ADD CONSTRAINT fk_appointments_client FOREIGN KEY (client_id) REFERENCES clients (id);
ALTER TABLE quotes ADD COLUMN client_id BIGINT UNSIGNED NULL,
    ADD CONSTRAINT fk_quotes_client FOREIGN KEY (client_id) REFERENCES clients (id);

INSERT INTO clients (name, email, phone, created_at)
SELECT contact_name, email_key, contact_phone, first_seen
FROM (
    SELECT contact_name, LOWER(TRIM(contact_email)) AS email_key, contact_phone,
        MIN(created_at) OVER (PARTITION BY LOWER(TRIM(contact_email))) AS first_seen,
        ROW_NUMBER() OVER (PARTITION BY LOWER(TRIM(contact_email)) ORDER BY created_at DESC, source, id DESC) AS rank_for_email
    FROM (
        SELECT id, contact_name, contact_email, contact_phone, created_at, 1 AS source FROM quotes
        UNION ALL
        SELECT id, contact_name, contact_email, contact_phone, created_at, 2 AS source FROM appointments
    ) contacts
) ranked
WHERE rank_for_email = 1;

UPDATE appointments a JOIN clients c ON c.email = LOWER(TRIM(a.contact_email)) SET a.client_id = c.id;
UPDATE quotes q JOIN clients c ON c.email = LOWER(TRIM(q.contact_email)) SET q.client_id = c.id;

CREATE TABLE orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    source_quote_id BIGINT UNSIGNED NULL UNIQUE,
    assignee_resource_id BIGINT UNSIGNED NULL,
    status ENUM('received','in_progress','completed','delivered','cancelled') NOT NULL DEFAULT 'received',
    due_on DATE NULL,
    notes TEXT NULL,
    deposit_expected DECIMAL(12,2) NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_orders_status_date (status,created_at),
    FOREIGN KEY (client_id) REFERENCES clients (id),
    FOREIGN KEY (source_quote_id) REFERENCES quotes (id),
    FOREIGN KEY (assignee_resource_id) REFERENCES booking_resources (id),
    CHECK (total_amount >= 0),
    CHECK (deposit_expected IS NULL OR (deposit_expected >= 0 AND deposit_expected <= total_amount))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NULL,
    label VARCHAR(180) NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    KEY ix_order_items_order_position (order_id, sort_order),
    FOREIGN KEY (order_id) REFERENCES orders (id),
    FOREIGN KEY (service_id) REFERENCES services (id),
    CHECK (quantity > 0),
    CHECK (unit_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    actor_id BIGINT UNSIGNED NOT NULL,
    status ENUM('received','in_progress','completed','delivered','cancelled') NOT NULL,
    snapshot JSON NOT NULL,
    occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_order_events_order (order_id, id),
    FOREIGN KEY (order_id) REFERENCES orders (id),
    FOREIGN KEY (actor_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
