-- Add notes column to coaching_session table
-- Run this SQL to update your existing database

ALTER TABLE `coaching_session` 
ADD COLUMN `notes` longtext DEFAULT NULL 
AFTER `status`;
