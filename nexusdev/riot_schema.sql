-- Riot Games API Schema Update for NexusPlay
-- Run this in phpMyAdmin SQL tab

-- Add Riot columns to user table
ALTER TABLE `user` 
    ADD COLUMN IF NOT EXISTS `riot_summoner_name` VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `riot_region` VARCHAR(20) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `riot_puuid` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `riot_summoner_id` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `riot_last_sync_at` DATETIME DEFAULT NULL;

-- Add Riot rank columns to statistic table
ALTER TABLE `statistic` 
    ADD COLUMN IF NOT EXISTS `rank_tier` VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `rank_division` VARCHAR(20) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `league_points` INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP;
