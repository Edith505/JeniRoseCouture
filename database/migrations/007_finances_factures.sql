CREATE TABLE finance_profile (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    issuer JSON NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoice_sequence (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    last_number BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB;
INSERT INTO invoice_sequence (id, last_number) VALUES (1, 0);

CREATE TABLE invoices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    number VARCHAR(30) NOT NULL UNIQUE,
    kind ENUM('invoice','credit') NOT NULL,
    reverses_id BIGINT UNSIGNED NULL UNIQUE,
    issue_on DATE NOT NULL,
    issue_at DATETIME NOT NULL,
    total_ht DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    total_ttc DECIMAL(12,2) NOT NULL,
    snapshot JSON NOT NULL,
    pdf MEDIUMBLOB NOT NULL,
    actor_id BIGINT UNSIGNED NOT NULL,
    KEY ix_invoices_date (issue_on, id),
    KEY ix_invoices_order (order_id, kind),
    FOREIGN KEY (order_id) REFERENCES orders (id),
    FOREIGN KEY (reverses_id) REFERENCES invoices (id),
    FOREIGN KEY (actor_id) REFERENCES users (id),
    CHECK ((kind = 'invoice' AND reverses_id IS NULL) OR (kind = 'credit' AND reverses_id IS NOT NULL)),
    CHECK (total_ht >= 0 AND tax_amount >= 0 AND total_ttc = total_ht + tax_amount)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoice_items (
    invoice_id BIGINT UNSIGNED NOT NULL,
    position SMALLINT UNSIGNED NOT NULL,
    description VARCHAR(180) NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL,
    unit_ht DECIMAL(14,6) NOT NULL,
    vat_basis_points SMALLINT UNSIGNED NOT NULL,
    amount_ht DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    amount_ttc DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (invoice_id, position),
    FOREIGN KEY (invoice_id) REFERENCES invoices (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    request_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    booked_on DATE NOT NULL,
    direction ENUM('payment','refund') NOT NULL,
    method ENUM('cash','card','transfer','cheque','other') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reference VARCHAR(140) NOT NULL,
    actor_id BIGINT UNSIGNED NOT NULL,
    KEY ix_payments_order (order_id, id),
    KEY ix_payments_date (booked_on, id),
    FOREIGN KEY (order_id) REFERENCES orders (id),
    FOREIGN KEY (actor_id) REFERENCES users (id),
    CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE expense_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE expenses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    request_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    booked_on DATE NOT NULL,
    description VARCHAR(180) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    actor_id BIGINT UNSIGNED NOT NULL,
    KEY ix_expenses_date (booked_on, id),
    FOREIGN KEY (category_id) REFERENCES expense_categories (id),
    FOREIGN KEY (actor_id) REFERENCES users (id),
    CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
