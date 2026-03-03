<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250207114000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add leaderboard and analytics tables: rank_history, achievement, player_achievement, game_match, match_player';
    }

    public function up(Schema $schema): void
    {
        // Create rank_history table
        $this->addSql('CREATE TABLE rank_history (
            id INT AUTO_INCREMENT NOT NULL,
            player_id INT NOT NULL,
            game_id INT NOT NULL,
            rank INT NOT NULL,
            elo_rating INT NOT NULL DEFAULT 1200,
            region VARCHAR(255) DEFAULT NULL,
            recorded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            season VARCHAR(50) DEFAULT NULL,
            INDEX idx_player_date (player_id, recorded_at),
            INDEX IDX_797777399E6F5DF (player_id),
            INDEX IDX_7977773E48FD905 (game_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE rank_history ADD CONSTRAINT FK_797777399E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE rank_history ADD CONSTRAINT FK_7977773E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');

        // Create achievement table
        $this->addSql('CREATE TABLE achievement (
            id INT AUTO_INCREMENT NOT NULL,
            game_id INT DEFAULT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            rarity VARCHAR(50) NOT NULL,
            icon VARCHAR(255) DEFAULT NULL,
            points INT NOT NULL DEFAULT 0,
            required_value INT NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_96737FF1E48FD905 (game_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE achievement ADD CONSTRAINT FK_96737FF1E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');

        // Create player_achievement table
        $this->addSql('CREATE TABLE player_achievement (
            id INT AUTO_INCREMENT NOT NULL,
            player_id INT NOT NULL,
            achievement_id INT NOT NULL,
            progress INT NOT NULL DEFAULT 0,
            is_unlocked TINYINT(1) NOT NULL DEFAULT 0,
            unlocked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_7A7B4A9299E6F5DF (player_id),
            INDEX IDX_7A7B4A92B3EC99FE (achievement_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE player_achievement ADD CONSTRAINT FK_7A7B4A9299E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE player_achievement ADD CONSTRAINT FK_7A7B4A92B3EC99FE FOREIGN KEY (achievement_id) REFERENCES achievement (id)');

        // Create game_match table
        $this->addSql('CREATE TABLE game_match (
            id INT AUTO_INCREMENT NOT NULL,
            game_id INT NOT NULL,
            team_a_id INT DEFAULT NULL,
            team_b_id INT DEFAULT NULL,
            team_a_name VARCHAR(100) DEFAULT NULL,
            team_b_name VARCHAR(100) DEFAULT NULL,
            team_a_score INT DEFAULT NULL,
            team_b_score INT DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT \'scheduled\',
            match_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            map VARCHAR(50) DEFAULT NULL,
            replay_url VARCHAR(255) DEFAULT NULL,
            INDEX IDX_4868BC8AE48FD905 (game_id),
            INDEX IDX_4868BC8AEA3FA723 (team_a_id),
            INDEX IDX_4868BC8AF88A08CD (team_b_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE game_match ADD CONSTRAINT FK_4868BC8AE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE game_match ADD CONSTRAINT FK_4868BC8AEA3FA723 FOREIGN KEY (team_a_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE game_match ADD CONSTRAINT FK_4868BC8AF88A08CD FOREIGN KEY (team_b_id) REFERENCES team (id)');

        // Create match_player table
        $this->addSql('CREATE TABLE match_player (
            id INT AUTO_INCREMENT NOT NULL,
            game_match_id INT NOT NULL,
            player_id INT NOT NULL,
            team_id INT DEFAULT NULL,
            kills INT NOT NULL DEFAULT 0,
            deaths INT NOT NULL DEFAULT 0,
            assists INT NOT NULL DEFAULT 0,
            position_x NUMERIC(10, 2) DEFAULT NULL,
            position_y NUMERIC(10, 2) DEFAULT NULL,
            is_winner TINYINT(1) NOT NULL DEFAULT 0,
            elo_change INT DEFAULT NULL,
            INDEX IDX_3976836481FA53F0 (game_match_id),
            INDEX IDX_3976836499E6F5DF (player_id),
            INDEX IDX_39768364296CD8AE (team_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE match_player ADD CONSTRAINT FK_3976836481FA53F0 FOREIGN KEY (game_match_id) REFERENCES game_match (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_player ADD CONSTRAINT FK_3976836499E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE match_player ADD CONSTRAINT FK_39768364296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_player DROP FOREIGN KEY FK_3976836481FA53F0');
        $this->addSql('ALTER TABLE match_player DROP FOREIGN KEY FK_3976836499E6F5DF');
        $this->addSql('ALTER TABLE match_player DROP FOREIGN KEY FK_39768364296CD8AE');
        $this->addSql('DROP TABLE match_player');

        $this->addSql('ALTER TABLE game_match DROP FOREIGN KEY FK_4868BC8AE48FD905');
        $this->addSql('ALTER TABLE game_match DROP FOREIGN KEY FK_4868BC8AEA3FA723');
        $this->addSql('ALTER TABLE game_match DROP FOREIGN KEY FK_4868BC8AF88A08CD');
        $this->addSql('DROP TABLE game_match');

        $this->addSql('ALTER TABLE player_achievement DROP FOREIGN KEY FK_7A7B4A9299E6F5DF');
        $this->addSql('ALTER TABLE player_achievement DROP FOREIGN KEY FK_7A7B4A92B3EC99FE');
        $this->addSql('DROP TABLE player_achievement');

        $this->addSql('ALTER TABLE achievement DROP FOREIGN KEY FK_96737FF1E48FD905');
        $this->addSql('DROP TABLE achievement');

        $this->addSql('ALTER TABLE rank_history DROP FOREIGN KEY FK_797777399E6F5DF');
        $this->addSql('ALTER TABLE rank_history DROP FOREIGN KEY FK_7977773E48FD905');
        $this->addSql('DROP TABLE rank_history');
    }
}
