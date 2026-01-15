-- ============================================
-- JWT Authentication Migration for Production
-- Database: loginveerles (or your production database name)
-- ============================================

-- STEP 1: Make session_token nullable (required for JWT sessions)
ALTER TABLE sessions 
    MODIFY COLUMN session_token CHAR(64) NULL DEFAULT NULL;

-- STEP 2: Add jti column for JWT ID tracking
ALTER TABLE sessions 
    ADD COLUMN jti CHAR(64) NULL 
    COMMENT 'JWT ID for tracking and revocation' 
    AFTER user_id;

-- STEP 3: Add revoked_at column for JWT revocation
ALTER TABLE sessions 
    ADD COLUMN revoked_at DATETIME DEFAULT NULL 
    COMMENT 'Timestamp when JWT was revoked';

-- STEP 4: Create unique index on jti for fast lookups
ALTER TABLE sessions 
    ADD UNIQUE KEY idx_jti (jti);

-- STEP 5: Verify the changes
DESCRIBE sessions;

-- You should see these columns:
-- - session_token (nullable)
-- - jti (nullable)
-- - revoked_at (nullable)

-- ============================================
-- OPTIONAL: Clean up old sessions (recommended)
-- ============================================

-- See how many old sessions exist
-- SELECT COUNT(*) as old_sessions FROM sessions WHERE jti IS NULL;

-- Delete old session-based sessions (uncomment to run)
-- DELETE FROM sessions WHERE jti IS NULL;

-- Or delete all sessions to force everyone to re-login (uncomment to run)
-- DELETE FROM sessions;

-- ============================================
-- Migration Complete!
-- ============================================
