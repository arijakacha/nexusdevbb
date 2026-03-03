<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260204160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user relationship, rank, statistics to player table; make team and game nullable';
    }

    public function up(Schema $schema): void
    {
        // Make team_id nullable
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65296CD8AE');
        $this->addSql('ALTER TABLE player CHANGE team_id team_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        
        // Make game_id nullable
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65E48FD905');
        $this->addSql('ALTER TABLE player CHANGE game_id game_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        
        // Add user_id column and foreign key
        $this->addSql('ALTER TABLE player ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65A76ED395 ON player (user_id)');
        
        // Add rank column
        $this->addSql('ALTER TABLE player ADD rank VARCHAR(50) DEFAULT NULL');
        
        // Add statistics column (JSON)
        $this->addSql('ALTER TABLE player ADD statistics JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove user relationship
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65A76ED395');
        $this->addSql('DROP INDEX UNIQ_98197A65A76ED395 ON player');
        $this->addSql('ALTER TABLE player DROP user_id');
        
        // Remove rank and statistics
        $this->addSql('ALTER TABLE player DROP rank');
        $this->addSql('ALTER TABLE player DROP statistics');
        
        // Make team_id not nullable again
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65296CD8AE');
        $this->addSql('ALTER TABLE player CHANGE team_id team_id INT NOT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        
        // Make game_id not nullable again
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65E48FD905');
        $this->addSql('ALTER TABLE player CHANGE game_id game_id INT NOT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }
}
