-- Update sessions table schema for JWT authentication
-- This migrates from the old session-based schema to JWT-compatible schema

-- Step 1: Add new columns
ALTER TABLE sessions 
    ADD COLUMN jti CHAR(64) NULL COMMENT 'JWT ID for tracking and revocation' AFTER user_id,
    ADD COLUMN revoked_at DATETIME DEFAULT NULL COMMENT 'Timestamp when JWT was revoked';

-- Step 2: Create index on jti for fast lookups
ALTER TABLE sessions 
    ADD UNIQUE KEY idx_jti (jti);

-- Step 3: Migrate existing data (copy session_token to jti if needed)
-- UPDATE sessions SET jti = session_token WHERE jti IS NULL;

-- Step 4: Make jti NOT NULL after data migration
-- ALTER TABLE sessions MODIFY COLUMN jti CHAR(64) NOT NULL;

-- Step 5: Optional - remove old session-based columns if not needed
-- ALTER TABLE sessions 
--     DROP COLUMN session_token,
--     DROP COLUMN ip,
--     DROP COLUMN user_agent_hash,
--     DROP COLUMN last_activity,
--     DROP COLUMN remember_flag;

-- Note: We're keeping both schemas for now for backwards compatibility
-- You can clean up old columns later if needed
