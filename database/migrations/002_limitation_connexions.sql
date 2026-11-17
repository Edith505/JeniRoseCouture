CREATE TABLE login_attempts (
    source_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    window_started_at DATETIME(6) NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (source_hash),
    KEY ix_login_attempts_window (window_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
