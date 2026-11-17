CREATE TABLE service_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(140) NOT NULL,
    name VARCHAR(140) NOT NULL,
    is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_service_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE services (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id BIGINT UNSIGNED NOT NULL,
    slug VARCHAR(140) NOT NULL,
    name VARCHAR(140) NOT NULL,
    description TEXT NOT NULL,
    conditions TEXT NOT NULL,
    examples TEXT NOT NULL,
    price_type ENUM('fixed', 'from', 'hourly', 'from_hourly', 'quote', 'custom') NOT NULL,
    price_amount DECIMAL(10,2) NULL,
    price_is_demo BOOLEAN NOT NULL DEFAULT TRUE,
    duration_minutes SMALLINT UNSIGNED NULL,
    service_mode ENUM('confirm', 'workshop', 'home', 'both') NOT NULL DEFAULT 'confirm',
    action_intent ENUM('information', 'appointment', 'quote') NOT NULL DEFAULT 'information',
    is_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    asset_name VARCHAR(150) NULL,
    archived_at DATETIME(6) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_services_slug (slug),
    KEY ix_services_catalogue (category_id, is_enabled, archived_at, sort_order),
    CONSTRAINT fk_services_category FOREIGN KEY (category_id) REFERENCES service_categories (id),
    CONSTRAINT chk_services_price CHECK (
        (price_type IN ('quote', 'custom') AND price_amount IS NULL) OR
        (price_type IN ('fixed', 'from', 'hourly', 'from_hourly') AND price_amount IS NOT NULL AND price_amount >= 0)
    ),
    CONSTRAINT chk_services_duration CHECK (duration_minutes IS NULL OR duration_minutes > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE service_images (
    service_id BIGINT UNSIGNED NOT NULL,
    image_webp MEDIUMBLOB NOT NULL,
    PRIMARY KEY (service_id),
    CONSTRAINT fk_service_images_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
