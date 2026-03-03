<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Riot Games API integration fields to User and Statistic tables';
    }

    public function up(Schema $schema): void
    {
        // Add Riot Games fields to User table
        $this->addSql('ALTER TABLE user ADD riot_summoner_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD riot_region VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD riot_puuid VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD riot_summoner_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD riot_last_sync_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Add Riot rank fields to Statistic table
        $this->addSql('ALTER TABLE statistic ADD rank_tier VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE statistic ADD rank_division VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE statistic ADD league_points INT DEFAULT NULL');
        $this->addSql('ALTER TABLE statistic ADD updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Set default updated_at for existing statistics
        $this->addSql('UPDATE statistic SET updated_at = created_at');
    }

    public function down(Schema $schema): void
    {
        // Remove Riot Games fields from User table
        $this->addSql('ALTER TABLE user DROP riot_summoner_name');
        $this->addSql('ALTER TABLE user DROP riot_region');
        $this->addSql('ALTER TABLE user DROP riot_puuid');
        $this->addSql('ALTER TABLE user DROP riot_summoner_id');
        $this->addSql('ALTER TABLE user DROP riot_last_sync_at');

        // Remove Riot rank fields from Statistic table
        $this->addSql('ALTER TABLE statistic DROP rank_tier');
        $this->addSql('ALTER TABLE statistic DROP rank_division');
        $this->addSql('ALTER TABLE statistic DROP league_points');
        $this->addSql('ALTER TABLE statistic DROP updated_at');
    }
}
