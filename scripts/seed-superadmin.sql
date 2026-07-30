-- SuperAdmin Fab610 — mot de passe initial : Fab610@Dyrsia26 (à changer après connexion)
-- Regénérer le hash : php -r "echo password_hash('Fab610@Dyrsia26', PASSWORD_BCRYPT), PHP_EOL;"

DELETE FROM tbl_users
WHERE LOWER(username) IN ('admin', 'administrator', 'root', 'superadmin')
  AND user_type = 'SuperAdmin';

ALTER TABLE tbl_users MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT;

INSERT INTO tbl_users (
    id,
    root,
    username,
    fullname,
    password,
    user_type,
    status,
    last_login,
    creationdate
) VALUES (
    1,
    0,
    'Fab610',
    'Super Administrateur',
    '$2y$12$VS8P8v5..kjPhgUDlpDNyeIxnb5nr4RjIfuBa/dHyh8mKEa/R/G0.',
    'SuperAdmin',
    'Active',
    NOW(),
    NOW()
);

INSERT INTO tbl_appconfig (setting, value)
VALUES ('superadmin_fab610_migrated', 'yes')
ON DUPLICATE KEY UPDATE value = 'yes';

INSERT INTO tbl_appconfig (setting, value)
VALUES ('superadmin_initial_pwd_v1', 'yes')
ON DUPLICATE KEY UPDATE value = 'yes';

ALTER TABLE tbl_users AUTO_INCREMENT = 2;
