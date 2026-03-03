-- Add Riot Games API integration columns

-- Add columns to user table
ALTER TABLE `user` 
    ADD COLUMN `riot_summoner_name` VARCHAR(100) DEFAULT NULL,
    ADD COLUMN `riot_region` VARCHAR(20) DEFAULT NULL,
    ADD COLUMN `riot_puuid` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `riot_summoner_id` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `riot_last_sync_at` DATETIME DEFAULT NULL;

-- Add columns to statistic table
ALTER TABLE `statistic`
    ADD COLUMN `rank_tier` VARCHAR(50) DEFAULT NULL,
    ADD COLUMN `rank_division` VARCHAR(20) DEFAULT NULL,
    ADD COLUMN `league_points` INT DEFAULT NULL,
    ADD COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
