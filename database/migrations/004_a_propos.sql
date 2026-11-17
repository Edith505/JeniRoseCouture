CREATE TABLE about_profiles (
    version ENUM('draft', 'published') NOT NULL,
    content JSON NOT NULL,
    is_demo BOOLEAN NOT NULL DEFAULT FALSE,
    portrait_webp MEDIUMBLOB NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE about_entries (
    version ENUM('draft', 'published') NOT NULL,
    entry_key CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    kind ENUM('education', 'experience', 'skill', 'value', 'photo') NOT NULL,
    title VARCHAR(160) NOT NULL,
    organisation VARCHAR(160) NOT NULL,
    period VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    responsibilities TEXT NOT NULL,
    competencies TEXT NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    visible BOOLEAN NOT NULL DEFAULT TRUE,
    is_demo BOOLEAN NOT NULL DEFAULT FALSE,
    archived BOOLEAN NOT NULL DEFAULT FALSE,
    image_webp MEDIUMBLOB NULL,
    PRIMARY KEY (version, entry_key),
    KEY ix_about_entries_order (version, kind, sort_order, entry_key),
    CONSTRAINT fk_about_entries_version FOREIGN KEY (version) REFERENCES about_profiles (version) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
