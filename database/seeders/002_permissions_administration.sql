INSERT INTO permissions (code) VALUES ('admin.access')
ON DUPLICATE KEY UPDATE code = permissions.code;

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles CROSS JOIN permissions
WHERE roles.code = 'admin' AND permissions.code = 'admin.access'
ON DUPLICATE KEY UPDATE role_id = role_permissions.role_id;
