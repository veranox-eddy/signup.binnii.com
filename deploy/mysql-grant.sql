-- The ONLY MySQL account the signup host holds: column-level read on two
-- columns of daycare.users. Verify with SHOW GRANTS — the output must be
-- exactly USAGE plus this single line.
CREATE USER 'binnii_signup_ro'@'<signup-host-ip>' IDENTIFIED BY '***';
GRANT SELECT (email, deleted_at) ON daycare.users TO 'binnii_signup_ro'@'<signup-host-ip>';
FLUSH PRIVILEGES;
