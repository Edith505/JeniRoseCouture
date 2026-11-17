INSERT INTO roles (code)
VALUES ('admin'), ('manager'), ('employee')
ON DUPLICATE KEY UPDATE code = roles.code;
