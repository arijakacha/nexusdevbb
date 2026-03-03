-- Add recent_matches column to user table for storing last 2 games
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `recent_matches` JSON DEFAULT NULL;
