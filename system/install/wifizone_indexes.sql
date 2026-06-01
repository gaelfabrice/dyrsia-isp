-- WifiZone — index de performance (§18)
ALTER TABLE tbl_transactions ADD INDEX IF NOT EXISTS idx_recharged_admin (recharged_on, admin_id);
ALTER TABLE tbl_user_recharges ADD INDEX IF NOT EXISTS idx_status_exp (status, expiration);
