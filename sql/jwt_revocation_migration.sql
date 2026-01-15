-- JWT Revocation Migration
-- Adds revoked_at column to sessions table for proper JWT revocation
-- Run this migration on your production database

-- Add revoked_at column
ALTER TABLE sessions 
    ADD COLUMN revoked_at DATETIME DEFAULT NULL
    COMMENT 'Timestamp when session/JWT was revoked';

-- Update table comment to clarify purpose
ALTER TABLE sessions 
    COMMENT = 'JWT session tracking for revocation capability';

-- Update session_token column comment
ALTER TABLE sessions 
    MODIFY COLUMN session_token CHAR(64) NOT NULL
    COMMENT 'JWT ID (jti claim) for revocation tracking';

-- Optional: Clean up any old session-based records if they exist
-- DELETE FROM sessions WHERE LENGTH(session_token) < 64;
