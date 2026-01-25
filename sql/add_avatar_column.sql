-- Add avatar_url column to users table
-- This stores the path to custom user avatar images

ALTER TABLE users 
ADD COLUMN avatar_url VARCHAR(255) NULL 
AFTER last_name;

-- Create uploads directory structure
-- Run these commands on your server:
-- mkdir -p assets/uploads/avatars
-- chmod 755 assets/uploads/avatars
