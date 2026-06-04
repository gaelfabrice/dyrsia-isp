-- Compte SuperAdmin par défaut (mot de passe : admin)
-- Exécuter dans phpMyAdmin sur la base dyrsi1328310_1bqyyb (adapter le nom si besoin).

DELETE FROM tbl_users WHERE username = 'admin';

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
    'admin',
    'Administrator',
    'd033e22ae348aeb5660fc2140aec35850c4da997',
    'SuperAdmin',
    'Active',
    NOW(),
    NOW()
);

ALTER TABLE tbl_users AUTO_INCREMENT = 2;
